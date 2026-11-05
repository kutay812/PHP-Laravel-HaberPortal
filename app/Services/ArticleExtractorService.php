<?php

declare(strict_types=1);

namespace App\Services;

use Dom\Element;
use Dom\HTMLDocument;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ArticleExtractorService
{
    private const USER_AGENT = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36';
    private const MAX_HTML_BYTES = 3_145_728; // 3 MB

    /** @var list<string> */
    private const ARTICLE_SELECTORS = [
        '[itemprop="articleBody"]',
        'article .article-body',
        'article .news-content',
        '.article-body',
        '.article-content',
        '.news-detail',
        '.news-content',
        '.haber-metni',
        '.habermetni',
        '.haber-detay',
        '.detay-metin',
        '.content-body',
        '.content-text',
        '.story-body',
        '.post-content',
        '.entry-content',
        'article',
        'main',
    ];

    /** @var list<string> */
    private const NOISE_PATTERNS = [
        'related', 'ilgili', 'benzer', 'tavsiye', 'onerilen',
        'share', 'paylas', 'social', 'sosyal',
        'comment', 'yorum', 'advert', 'reklam', 'sponsor', 'banner',
        'newsletter', 'abone', 'bulten', 'google-news',
        'breadcrumb', 'etiket', 'tag-list', 'tags',
        'author-bio', 'yazar-bilgi', 'read-more', 'devami',
        'manset-slider', 'galeri-link', 'video-player', 'popup', 'cookie',
    ];

    public function __construct(
        private readonly UrlSecurityService $urlSecurity,
    ) {}

    /**
     * Fetch source webpage safely and extract clean full article text, lead description, and OG image.
     *
     * @return array{body: string, description: string, image_url: ?string}
     */
    public function extractFromUrl(string $url, ?string $title = null): array
    {
        $empty = ['body' => '', 'description' => '', 'image_url' => null];

        if (!$this->urlSecurity->isSafePublicUrl($url)) {
            return $empty;
        }

        try {
            $response = Http::timeout(12)
                ->connectTimeout(5)
                ->withOptions($this->urlSecurity->safeRedirectOptions(3))
                ->withHeaders([
                    'User-Agent'      => self::USER_AGENT,
                    'Accept'          => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                    'Accept-Language' => 'tr-TR,tr;q=0.9,en-US;q=0.7,en;q=0.6',
                ])
                ->get($url);

            return $this->extractFromResponse($response, $title);
        } catch (\Throwable $e) {
            Log::debug('ArticleExtractor fetch failed', [
                'url'   => $url,
                'error' => $e->getMessage(),
            ]);

            return $empty;
        }
    }

    /**
     * Extract article data from an already-completed HTTP response (supports Http::pool batching).
     *
     * @return array{body: string, description: string, image_url: ?string}
     */
    public function extractFromResponse(Response $response, ?string $title = null): array
    {
        $empty = ['body' => '', 'description' => '', 'image_url' => null];

        if (!$response->successful()) {
            return $empty;
        }

        $contentType = strtolower($response->header('Content-Type'));
        if ($contentType !== '' && !str_contains($contentType, 'html') && !str_contains($contentType, 'xml')) {
            return $empty;
        }

        $rawBody = $response->body();
        if ($rawBody === '' || strlen($rawBody) > self::MAX_HTML_BYTES) {
            return $empty;
        }

        $utf8Html = $this->normalizeEncoding($rawBody, $contentType);

        return $this->extractFromHtml($utf8Html, $title);
    }

    /**
     * Extract full article text, lead summary, and primary image from raw HTML.
     *
     * @return array{body: string, description: string, image_url: ?string}
     */
    public function extractFromHtml(string $html, ?string $title = null): array
    {
        $trimmed = trim($html);
        if ($trimmed === '') {
            return ['body' => '', 'description' => '', 'image_url' => null];
        }

        $jsonLd = $this->extractFromJsonLd($trimmed);
        $domData = $this->extractFromDom($trimmed, $title);

        $jsonLdLength = mb_strlen($jsonLd['body'], 'UTF-8');
        $domLength = mb_strlen($domData['body'], 'UTF-8');

        // Prefer whichever source yields fuller clean prose; JSON-LD wins ties as it is free of UI noise
        $bestBody = ($jsonLdLength >= 320 && $jsonLdLength >= (int) ($domLength * 0.75))
            ? $jsonLd['body']
            : ($domLength >= $jsonLdLength ? $domData['body'] : $jsonLd['body']);

        // Prepend lead/description if it adds distinct context missing from the body opening
        $bestDescription = $jsonLd['description'] !== '' ? $jsonLd['description'] : $domData['description'];
        if (
            $bestDescription !== ''
            && mb_strlen($bestDescription, 'UTF-8') >= 50
            && $bestBody !== ''
            && !str_contains(
                mb_strtolower(mb_substr($bestBody, 0, 350, 'UTF-8'), 'UTF-8'),
                mb_strtolower(mb_substr($bestDescription, 0, 45, 'UTF-8'), 'UTF-8')
            )
        ) {
            $bestBody = $bestDescription . "\n\n" . $bestBody;
        }

        return [
            'body'        => trim($bestBody),
            'description' => trim($bestDescription),
            'image_url'   => $jsonLd['image_url'] ?? $domData['image_url'],
        ];
    }

    /**
     * Parse Schema.org NewsArticle / Article JSON-LD blocks.
     *
     * @return array{body: string, description: string, image_url: ?string}
     */
    private function extractFromJsonLd(string $html): array
    {
        $body = '';
        $description = '';
        $imageUrl = null;

        if (!preg_match_all('/<script[^>]*type=["\']application\/ld\+json["\'][^>]*>(.*?)<\/script>/is', $html, $matches)) {
            return ['body' => '', 'description' => '', 'image_url' => null];
        }

        foreach ($matches[1] as $rawJson) {
            $cleanedJson = trim(html_entity_decode($rawJson, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            // Remove raw control characters that occasionally break JSON decoding on news portals
            $cleanedJson = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/u', ' ', $cleanedJson) ?? $cleanedJson;

            $decoded = json_decode($cleanedJson, true);
            if (!is_array($decoded)) {
                continue;
            }

            foreach ($this->flattenJsonLdNodes($decoded) as $node) {
                $type = $node['@type'] ?? '';
                $types = is_array($type) ? $type : [$type];
                $isArticle = false;
                foreach ($types as $t) {
                    if (is_string($t) && preg_match('/Article|News|Posting|Report/i', $t)) {
                        $isArticle = true;
                        break;
                    }
                }

                if (!$isArticle && !isset($node['articleBody'])) {
                    continue;
                }

                if (!empty($node['articleBody']) && is_string($node['articleBody'])) {
                    $candidate = $this->cleanPlainText($node['articleBody']);
                    if (mb_strlen($candidate, 'UTF-8') > mb_strlen($body, 'UTF-8')) {
                        $body = $candidate;
                    }
                }

                if ($description === '' && !empty($node['description']) && is_string($node['description'])) {
                    $description = $this->cleanPlainText($node['description']);
                }

                if ($imageUrl === null && isset($node['image'])) {
                    $imageUrl = $this->extractJsonLdImage($node['image']);
                }
            }
        }

        return [
            'body'        => $body,
            'description' => $description,
            'image_url'   => $imageUrl,
        ];
    }

    /**
     * @param array<mixed> $data
     * @return list<array<string, mixed>>
     */
    private function flattenJsonLdNodes(array $data): array
    {
        $nodes = [];

        if (isset($data['@type']) || isset($data['articleBody'])) {
            /** @var array<string, mixed> $data */
            $nodes[] = $data;
        }

        if (isset($data['@graph']) && is_array($data['@graph'])) {
            foreach ($data['@graph'] as $item) {
                if (is_array($item)) {
                    $nodes = array_merge($nodes, $this->flattenJsonLdNodes($item));
                }
            }
        }

        if (isset($data['mainEntity']) && is_array($data['mainEntity'])) {
            $nodes = array_merge($nodes, $this->flattenJsonLdNodes($data['mainEntity']));
        }

        if (array_is_list($data)) {
            foreach ($data as $item) {
                if (is_array($item)) {
                    $nodes = array_merge($nodes, $this->flattenJsonLdNodes($item));
                }
            }
        }

        return $nodes;
    }

    private function extractJsonLdImage(mixed $image): ?string
    {
        if (is_string($image) && filter_var($image, FILTER_VALIDATE_URL)) {
            return $image;
        }

        if (is_array($image)) {
            if (!empty($image['url']) && is_string($image['url']) && filter_var($image['url'], FILTER_VALIDATE_URL)) {
                return $image['url'];
            }
            if (isset($image[0])) {
                return $this->extractJsonLdImage($image[0]);
            }
        }

        return null;
    }

    /**
     * Extract clean paragraphs using PHP 8.5's native HTML5 DOM parser (\Dom\HTMLDocument).
     *
     * @return array{body: string, description: string, image_url: ?string}
     */
    private function extractFromDom(string $html, ?string $title = null): array
    {
        try {
            $doc = HTMLDocument::createFromString($html, LIBXML_NOERROR);
        } catch (\Throwable) {
            return ['body' => '', 'description' => '', 'image_url' => null];
        }

        $description = $this->extractMetaContent($doc, [
            'meta[property="og:description"]',
            'meta[name="description"]',
            'meta[name="twitter:description"]',
        ]);

        $imageUrl = $this->extractMetaContent($doc, [
            'meta[property="og:image"]',
            'meta[name="twitter:image"]',
        ]);
        if ($imageUrl !== '' && !filter_var($imageUrl, FILTER_VALIDATE_URL)) {
            $imageUrl = '';
        }

        // Remove non-editorial tags first
        foreach (['script', 'style', 'noscript', 'nav', 'header', 'footer', 'aside', 'form', 'iframe', 'svg', 'button', 'figure figcaption'] as $selector) {
            foreach ($doc->querySelectorAll($selector) as $node) {
                $node->parentNode?->removeChild($node);
            }
        }

        // Remove noise blocks by class/id
        foreach ($doc->querySelectorAll('[class], [id]') as $el) {
            $tagName = strtolower($el->tagName);
            if (in_array($tagName, ['html', 'body', 'main', 'article'], true)) {
                continue;
            }
            $signature = strtolower(($el->getAttribute('class') ?? '') . ' ' . ($el->getAttribute('id') ?? ''));
            foreach (self::NOISE_PATTERNS as $noise) {
                if (str_contains($signature, $noise)) {
                    $el->parentNode?->removeChild($el);
                    break;
                }
            }
        }

        $bestParagraphs = [];
        $bestCharCount = 0;

        foreach (self::ARTICLE_SELECTORS as $selector) {
            foreach ($doc->querySelectorAll($selector) as $container) {
                $paragraphs = $this->collectParagraphsFromContainer($container, $title);
                $charCount = array_sum(array_map(static fn (string $p): int => mb_strlen($p, 'UTF-8'), $paragraphs));

                if ($charCount > $bestCharCount) {
                    $bestCharCount = $charCount;
                    $bestParagraphs = $paragraphs;
                }
            }

            if ($bestCharCount >= 650) {
                break;
            }
        }

        return [
            'body'        => implode("\n\n", $bestParagraphs),
            'description' => $description,
            'image_url'   => $imageUrl !== '' ? $imageUrl : null,
        ];
    }

    /**
     * @return list<string>
     */
    private function collectParagraphsFromContainer(Element $container, ?string $title = null): array
    {
        $paragraphs = [];
        $seen = [];
        $normalizedTitle = $title !== null ? mb_strtolower(trim($title), 'UTF-8') : '';

        $nodes = $container->querySelectorAll('p, h2, h3, blockquote');
        foreach ($nodes as $node) {
            $text = $this->cleanPlainText($node->textContent ?? '');
            if ($text === '') {
                continue;
            }

            $len = mb_strlen($text, 'UTF-8');
            $tagName = strtolower($node->tagName);

            if (in_array($tagName, ['h2', 'h3'], true)) {
                if ($len < 12 || $len > 140) {
                    continue;
                }
            } elseif ($len < 35) {
                continue;
            }

            if ($this->isBoilerplateLine($text)) {
                continue;
            }

            $norm = mb_strtolower($text, 'UTF-8');
            if ($normalizedTitle !== '' && $norm === $normalizedTitle) {
                continue;
            }

            if (isset($seen[$norm])) {
                continue;
            }
            $seen[$norm] = true;

            $paragraphs[] = $text;
        }

        // Fallback if container uses <br><br> instead of <p> tags
        if ($paragraphs === []) {
            $rawText = $container->innerHTML;
            $chunks = preg_split('/<br\s*\/?>\s*<br\s*\/?>|<\/div>\s*<div[^>]*>/i', $rawText) ?: [];
            foreach ($chunks as $chunk) {
                $text = $this->cleanPlainText($chunk);
                if (mb_strlen($text, 'UTF-8') >= 45 && !$this->isBoilerplateLine($text)) {
                    $paragraphs[] = $text;
                }
            }
        }

        return $paragraphs;
    }

    /**
     * @param list<string> $selectors
     */
    private function extractMetaContent(HTMLDocument $doc, array $selectors): string
    {
        foreach ($selectors as $selector) {
            $el = $doc->querySelector($selector);
            if ($el instanceof Element) {
                $content = $this->cleanPlainText($el->getAttribute('content') ?? '');
                if ($content !== '') {
                    return $content;
                }
            }
        }

        return '';
    }

    private function isBoilerplateLine(string $text): bool
    {
        $patterns = [
            '/google\s+news/iu',
            '/abone\s+ol(un|mak)?/iu',
            '/haberin\s+devam[ıi]/iu',
            '/ilginizi\s+çekebilir/iu',
            '/tüm\s+haklar[ıi]\s+sakl[ıi]d[ıi]r/iu',
            '/telif\s+hakk[ıi]/iu',
            '/iktibas\s+edilemez/iu',
            '/internet\s+sitesinde\s+yay[ıi]nlanan/iu',
            '/izinsiz\s+(kullan[ıi]lamaz|kopyalanamaz)/iu',
            '/emoji\s+ile\s+tepki/iu',
            '/çerez\s+politikas[ıi]/iu',
            '/kvkk\s+ayd[ıi]nlatma/iu',
            '/bizi\s+takip\s+edin/iu',
            '/whatsapp\s+ihbar/iu',
            '/uygulamam[ıi]z[ıi]\s+indirin/iu',
            '/^(kaynak|editör|muhabir|fotoğraf|etiketler)\s*:/iu',
            '/^son\s+güncelleme\s*:/iu',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text)) {
                return true;
            }
        }

        return false;
    }

    private function cleanPlainText(string $text): string
    {
        $decoded = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $decoded = str_replace(["\r\n", "\r", "\xC2\xA0"], ["\n", "\n", ' '], $decoded);
        $lines = array_map(
            static fn (string $line): string => trim(preg_replace('/[ \t]+/u', ' ', $line) ?? $line),
            explode("\n", $decoded)
        );
        $lines = array_values(array_filter($lines, static fn (string $line): bool => $line !== ''));

        return implode("\n\n", $lines);
    }

    private function normalizeEncoding(string $html, string $contentType): string
    {
        $charset = 'UTF-8';
        if (preg_match('/charset=([a-zA-Z0-9\-_]+)/i', $contentType, $m)) {
            $charset = strtoupper($m[1]);
        } elseif (preg_match('/<meta[^>]+charset=["\']?([a-zA-Z0-9\-_]+)/i', substr($html, 0, 4096), $m)) {
            $charset = strtoupper($m[1]);
        }

        if (!in_array($charset, ['UTF-8', 'UTF8'], true)) {
            $converted = @mb_convert_encoding($html, 'UTF-8', $charset);
            if (is_string($converted) && $converted !== '') {
                return $converted;
            }
        }

        if (!mb_check_encoding($html, 'UTF-8')) {
            return mb_convert_encoding($html, 'UTF-8', 'WINDOWS-1254,ISO-8859-9,UTF-8');
        }

        return $html;
    }
}
