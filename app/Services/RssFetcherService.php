<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\NewsStatus;
use App\Models\Category;
use App\Models\Image;
use App\Models\News;
use App\Models\NewsSource;
use App\Models\Tag;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class RssFetcherService
{
    private const MAX_IMAGE_BYTES = 10_485_760; // 10 MB
    private const MIN_FULL_CONTENT_CHARS = 450;
    private const MAX_ITEMS_PER_FEED = 25;
    private const MAX_EXTRACTIONS_PER_SOURCE = 15;
    private const MAX_CONSECUTIVE_FAILURES = 10;

    private int $extractionsThisSource = 0;

    /**
     * @var array{total: int, new: int, skipped: int, errors: int}
     */
    protected array $fetchResults = [
        'total'   => 0,
        'new'     => 0,
        'skipped' => 0,
        'errors'  => 0,
    ];

    /**
     * In-memory category cache for zero-query category matching across feed items.
     *
     * @var Collection<int, Category>|null
     */
    private ?Collection $categoriesCache = null;

    /**
     * @var array<string, Category>
     */
    private array $categoriesBySlug = [];

    public function __construct(
        private readonly ArticleExtractorService $extractor,
        private readonly TurkishArticleRewriterService $rewriter,
        private readonly UrlSecurityService $urlSecurity,
    ) {}

    /**
     * Tüm aktif kaynaklardan haberleri çek.
     *
     * @return array<string, array<string, int|string>>
     */
    public function fetchAll(bool $force = false): array
    {
        $sources = NewsSource::active()->get();
        $results = [];

        foreach ($sources as $source) {
            if ($force || $source->shouldFetch()) {
                $results[$source->name] = $this->fetchFromSource($source);
            }
        }

        return $results;
    }

    /**
     * Belirli bir kaynaktan haberleri çek.
     *
     * @return array<string, int|string>
     */
    public function fetchFromSource(NewsSource $source): array
    {
        $this->fetchResults = ['total' => 0, 'new' => 0, 'skipped' => 0, 'errors' => 0];
        $this->extractionsThisSource = 0;

        if (!$this->urlSecurity->isSafePublicUrl((string) $source->url)) {
            $this->recordSourceFailure($source, "SSRF/Invalid URL: {$source->url}");

            return ['error' => 'Invalid or blocked source URL'];
        }

        try {
            $response = Http::timeout(25)
                ->connectTimeout(8)
                ->withOptions($this->urlSecurity->safeRedirectOptions(3))
                ->withUserAgent('HaberPortaliBot/2.0 (+http://localhost)')
                ->get((string) $source->url);

            if (!$response->successful()) {
                $this->recordSourceFailure($source, "HTTP {$response->status()}");

                return ['error' => "HTTP {$response->status()}"];
            }

            $previousLibxml = libxml_use_internal_errors(true);
            try {
                $xml = simplexml_load_string($response->body(), \SimpleXMLElement::class, LIBXML_NOCDATA | LIBXML_NONET);
            } finally {
                libxml_clear_errors();
                libxml_use_internal_errors($previousLibxml);
            }

            if ($xml === false) {
                $this->recordSourceFailure($source, 'Invalid XML');

                return ['error' => 'Invalid XML'];
            }

            if (isset($xml->channel->item)) {
                $items = $xml->channel->item;
                $isAtom = false;
            } elseif (isset($xml->entry)) {
                $items = $xml->entry;
                $isAtom = true;
            } elseif (isset($xml->item)) {
                $items = $xml->item;
                $isAtom = false;
            } else {
                return ['error' => 'Unknown feed format'];
            }

            $this->ensureCategoriesLoaded();

            $processedCount = 0;
            foreach ($items as $item) {
                if ($processedCount >= self::MAX_ITEMS_PER_FEED) {
                    break;
                }
                $this->processItem($item, $source, $isAtom);
                $processedCount++;
            }

            $source->update([
                'last_fetched_at' => now(),
                'failed_fetches'  => 0,
                'total_fetched'   => (int) $source->total_fetched + $this->fetchResults['new'],
            ]);
        } catch (\Throwable $e) {
            $this->recordSourceFailure($source, $e->getMessage());
            $this->fetchResults['errors']++;
        }

        return $this->fetchResults;
    }

    private function recordSourceFailure(NewsSource $source, string $reason): void
    {
        $newFailures = ((int) $source->failed_fetches) + 1;
        $updates = ['failed_fetches' => $newFailures];

        if ($newFailures >= self::MAX_CONSECUTIVE_FAILURES && $source->is_active) {
            $updates['is_active'] = false;
            Log::error("RSS Circuit Breaker: '{$source->name}' art arda {$newFailures} kez hata verdiği için otomatik pasife alındı. Son hata: {$reason}");
        } else {
            Log::warning("RSS Fetch Error ({$newFailures}/" . self::MAX_CONSECUTIVE_FAILURES . "): {$source->name} - {$reason}");
        }

        $source->update($updates);
    }

    /**
     * Tek bir feed öğesini işle: tam metin çıkar + yerel Türkçe özgünleştirme motorundan geçir.
     */
    protected function processItem(\SimpleXMLElement $item, NewsSource $source, bool $isAtom = false): void
    {
        $this->fetchResults['total']++;
        $title = '';

        try {
            $title = $this->extractTitle($item);
            $link = $this->extractLink($item, $isAtom);
            $rawDescription = $this->extractDescription($item, $isAtom);
            $rawContent = $this->extractContent($item, $isAtom);
            $pubDate = $this->extractPubDate($item, $isAtom);
            $author = $this->extractAuthor($item, $isAtom);
            $imageUrl = $this->extractImage($item);
            $categories = $this->extractCategories($item, $isAtom);

            if ($title === '' || $link === '') {
                $this->fetchResults['skipped']++;
                return;
            }

            if (News::withTrashed()->where('source_url', $link)->exists()) {
                $this->fetchResults['skipped']++;
                return;
            }

            // Eğer RSS içeriği kısa bir özetten ibaretse kaynak sayfadan tam haber metnini çıkar (kaynak başına üst sınır korumalı)
            $plainRssLen = mb_strlen(trim(strip_tags($rawContent !== '' ? $rawContent : $rawDescription)), 'UTF-8');
            if ($plainRssLen < self::MIN_FULL_CONTENT_CHARS && $this->extractionsThisSource < self::MAX_EXTRACTIONS_PER_SOURCE) {
                $this->extractionsThisSource++;
                $extracted = $this->extractor->extractFromUrl($link, $title);
                if (mb_strlen($extracted['body'], 'UTF-8') > $plainRssLen) {
                    $rawContent = $extracted['body'];
                }
                if ($rawDescription === '' && $extracted['description'] !== '') {
                    $rawDescription = $extracted['description'];
                }
                if ($imageUrl === null && $extracted['image_url'] !== null) {
                    $imageUrl = $extracted['image_url'];
                }
            }

            // Yerel Türkçe Özgünleştirme & Yapılandırma & HTML Sanitization
            $rewritten = $this->rewriter->rewrite($title, $rawContent, $rawDescription);
            $finalContent = $rewritten['content'];
            $finalDescription = $rewritten['description'];

            $contentHash = News::generateContentHash($title, $finalContent);
            if (News::withTrashed()->where('content_hash', $contentHash)->exists()) {
                $this->fetchResults['skipped']++;
                return;
            }

            $categoryIds = $this->matchCategories($categories, $source, $title, $finalDescription);
            $primaryCategoryId = $categoryIds[0] ?? $this->firstCategoryId();
            if ($primaryCategoryId === null) {
                $this->fetchResults['errors']++;
                return;
            }

            $slug = News::generateUniqueSlug($title);

            $imageId = null;
            if ($imageUrl !== null && $imageUrl !== '') {
                $imageId = $this->downloadAndSaveImage($imageUrl, $slug);
            }

            $isFeatured = $imageId !== null && random_int(1, 100) <= 15;

            $news = News::create([
                'title'               => Str::limit($title, 200, ''),
                'description'         => Str::limit($finalDescription, 300, ''),
                'content'             => $finalContent,
                'category_id'         => $primaryCategoryId,
                'image_id'            => $imageId,
                'user_id'             => null,
                'news_source_id'      => $source->id,
                'source_url'          => Str::limit($link, 500, ''),
                'source_author'       => $author !== null && $author !== '' ? Str::limit($author, 200, '') : $source->name,
                'source_published_at' => $pubDate,
                'is_active'           => true,
                'is_breaking'         => false,
                'is_featured'         => $isFeatured,
                'status'              => $source->auto_publish ? NewsStatus::Published->value : NewsStatus::Pending->value,
                'slug'                => $slug,
                'meta_title'          => Str::limit($title, 200, ''),
                'meta_description'    => Str::limit($finalDescription, 300, ''),
                'content_hash'        => $contentHash,
            ]);

            if ($categoryIds !== []) {
                $news->categories()->sync($categoryIds);
            }

            $this->autoTag($news, $title, $categories);

            $this->fetchResults['new']++;
        } catch (\Throwable $e) {
            Log::warning("RSS Item Error: {$e->getMessage()}", [
                'source' => $source->name,
                'title'  => $title !== '' ? $title : 'unknown',
            ]);
            $this->fetchResults['errors']++;
        }
    }

    // ─── EXTRACT METHODS ──────────────────────────────

    protected function extractTitle(\SimpleXMLElement $item): string
    {
        return trim(html_entity_decode(strip_tags((string) $item->title), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }

    protected function extractLink(\SimpleXMLElement $item, bool $isAtom): string
    {
        if ($isAtom) {
            foreach ($item->link as $link) {
                $attrs = $link->attributes();
                if (isset($attrs['href'])) {
                    return trim((string) $attrs['href']);
                }
            }

            return '';
        }

        return trim((string) $item->link);
    }

    protected function extractDescription(\SimpleXMLElement $item, bool $isAtom): string
    {
        if ($isAtom) {
            return trim((string) ($item->summary ?? $item->content ?? ''));
        }

        return trim((string) $item->description);
    }

    protected function extractContent(\SimpleXMLElement $item, bool $isAtom): string
    {
        if ($isAtom) {
            return trim((string) ($item->content ?? ''));
        }

        $namespaces = $item->getNamespaces(true);
        if (isset($namespaces['content'])) {
            $contentNs = $item->children($namespaces['content']);
            if (isset($contentNs->encoded)) {
                return trim((string) $contentNs->encoded);
            }
        }

        return '';
    }

    protected function extractPubDate(\SimpleXMLElement $item, bool $isAtom): ?\DateTimeImmutable
    {
        try {
            $dateStr = $isAtom
                ? trim((string) ($item->updated ?? $item->published ?? ''))
                : trim((string) $item->pubDate);

            return $dateStr !== '' ? new \DateTimeImmutable($dateStr) : null;
        } catch (\Throwable) {
            return null;
        }
    }

    protected function extractAuthor(\SimpleXMLElement $item, bool $isAtom): ?string
    {
        if ($isAtom && isset($item->author->name)) {
            return trim((string) $item->author->name);
        }

        $namespaces = $item->getNamespaces(true);
        if (isset($namespaces['dc'])) {
            $dc = $item->children($namespaces['dc']);
            if (isset($dc->creator)) {
                return trim((string) $dc->creator);
            }
        }

        $author = trim((string) ($item->author ?? ''));

        return $author !== '' ? $author : null;
    }

    protected function extractImage(\SimpleXMLElement $item): ?string
    {
        $namespaces = $item->getNamespaces(true);
        if (isset($namespaces['media'])) {
            $media = $item->children($namespaces['media']);
            if (isset($media->content)) {
                $attrs = $media->content->attributes();
                if (isset($attrs['url'])) {
                    return trim((string) $attrs['url']);
                }
            }
            if (isset($media->thumbnail)) {
                $attrs = $media->thumbnail->attributes();
                if (isset($attrs['url'])) {
                    return trim((string) $attrs['url']);
                }
            }
        }

        if (isset($item->enclosure)) {
            $attrs = $item->enclosure->attributes();
            if (isset($attrs['url'])) {
                return trim((string) $attrs['url']);
            }
        }

        $desc = (string) $item->description;
        if (preg_match('/<img[^>]+src=["\']([^"\']+)/i', $desc, $matches)) {
            return trim($matches[1]);
        }

        if (isset($namespaces['content'])) {
            $content = $item->children($namespaces['content']);
            if (isset($content->encoded)) {
                $encoded = (string) $content->encoded;
                if (preg_match('/<img[^>]+src=["\']([^"\']+)/i', $encoded, $matches)) {
                    return trim($matches[1]);
                }
            }
        }

        return null;
    }

    /**
     * @return list<string>
     */
    protected function extractCategories(\SimpleXMLElement $item, bool $isAtom): array
    {
        $categories = [];

        if ($isAtom) {
            foreach ($item->category ?? [] as $cat) {
                $attrs = $cat->attributes();
                $categories[] = trim((string) ($attrs['term'] ?? $cat));
            }
        } else {
            foreach ($item->category ?? [] as $cat) {
                $categories[] = trim((string) $cat);
            }
        }

        return array_values(array_filter($categories, static fn (string $c): bool => $c !== ''));
    }

    // ─── HELPER METHODS ───────────────────────────────

    private function ensureCategoriesLoaded(): void
    {
        if ($this->categoriesCache !== null) {
            return;
        }

        $this->categoriesCache = Category::all();
        $this->categoriesBySlug = [];

        foreach ($this->categoriesCache as $cat) {
            $this->categoriesBySlug[Str::slug($cat->slug)] = $cat;
            $this->categoriesBySlug[Str::slug($cat->name)] = $cat;
        }
    }

    private function findCategoryBySlug(string $slug): ?Category
    {
        $this->ensureCategoriesLoaded();
        $normalized = Str::slug($slug);

        return $this->categoriesBySlug[$normalized] ?? null;
    }

    private function firstCategoryId(): ?int
    {
        $this->ensureCategoriesLoaded();
        $first = $this->categoriesCache?->first();

        return $first ? (int) $first->id : null;
    }

    /**
     * Kaynak kategorisini ve içeriği sıfır ek SQL sorgusuyla yerel kategorilere eşleştir.
     *
     * @param list<string> $sourceCategories
     * @return list<int>
     */
    protected function matchCategories(array $sourceCategories, NewsSource $source, string $title = '', string $description = ''): array
    {
        $this->ensureCategoriesLoaded();

        $matchedIds = [];
        $textToAnalyze = mb_strtolower($title . ' ' . strip_tags($description), 'UTF-8');

        // 1. Kaynak eşleştirme tablosu
        if (is_array($source->category_mappings)) {
            foreach ($sourceCategories as $sourceCat) {
                $normalized = mb_strtolower(trim($sourceCat), 'UTF-8');
                if (isset($source->category_mappings[$normalized])) {
                    $matchedIds[] = (int) $source->category_mappings[$normalized];
                }
            }
        }

        // 2. İsim ve slug benzerliği (bellek içi)
        foreach ($sourceCategories as $sourceCat) {
            $normalized = mb_strtolower(trim($sourceCat), 'UTF-8');
            if ($normalized === '') {
                continue;
            }

            $bySlug = $this->findCategoryBySlug($normalized);
            if ($bySlug !== null) {
                $matchedIds[] = (int) $bySlug->id;
                continue;
            }

            foreach ($this->categoriesCache ?? [] as $cat) {
                $catLower = mb_strtolower($cat->name, 'UTF-8');
                if (str_contains($catLower, $normalized) || str_contains($normalized, $catLower)) {
                    $matchedIds[] = (int) $cat->id;
                }
            }
        }

        // 3. Anahtar kelime eşleştirmesi (ASCII-uyumlu standart slug anahtarları)
        $keywordMap = [
            'spor'      => ['sport', 'futbol', 'basketbol', 'voleybol', 'fenerbahçe', 'galatasaray', 'beşiktaş', 'trabzonspor', 'lig', 'maç', 'süper lig', 'şampiyon', 'milli takım'],
            'ekonomi'   => ['economy', 'finance', 'borsa', 'dolar', 'euro', 'piyasa', 'altın', 'faiz', 'enflasyon', 'tcmb', 'kredi', 'vergi', 'finans', 'zam', 'kripto', 'bitcoin'],
            'teknoloji' => ['technology', 'tech', 'bilişim', 'yazılım', 'yapay zeka', 'telefon', 'apple', 'samsung', 'google', 'sosyal medya', 'oyun', 'uzay', 'nasa'],
            'bilim'     => ['science', 'bilim', 'araştırma', 'arkeoloji', 'keşif', 'evren', 'fosil', 'genetik', 'biyoloji', 'fizik', 'kimya'],
            'dunya'     => ['world', 'international', 'uluslararası', 'abd', 'rusya', 'ukrayna', 'avrupa', 'gazze', 'israil', 'küresel', 'dünya'],
            'turkiye'   => ['türkiye', 'ankara', 'istanbul', 'izmir', 'chp', 'ak parti', 'tbmm', 'bakan', 'cumhurbaşkanı', 'sondakika'],
            'saglik'    => ['health', 'sağlık', 'tıp', 'hastalık', 'doktor', 'hastane', 'kanser', 'kalp', 'beslenme', 'diyet', 'virüs', 'salgın'],
            'yasam'     => ['yaşam', 'hayat', 'eğitim', 'okul', 'öğrenci', 'üniversite', 'insan', 'toplum'],
            'magazin'   => ['magazin', 'ünlüler', 'dizi', 'oyuncu', 'şarkıcı', 'konser', 'televizyon'],
            'otomobil'  => ['otomobil', 'araba', 'motor', 'araç', 'togg', 'tesla', 'elektrikli', 'otomotiv', 'suv', 'sedan'],
            'kultur'    => ['culture', 'sanat', 'sinema', 'müzik', 'tiyatro', 'kitap', 'yazar', 'tarih', 'kültür'],
            'gundem'    => ['gündem', 'siyaset', 'politika', 'politics', 'haber'],
        ];

        foreach ($keywordMap as $catSlug => $keywords) {
            $category = $this->findCategoryBySlug($catSlug);
            if ($category === null) {
                continue;
            }

            foreach ($keywords as $keyword) {
                if (preg_match('/(?<![\p{L}\p{N}])' . preg_quote($keyword, '/') . '(?![\p{L}\p{N}])/iu', $textToAnalyze)) {
                    $matchedIds[] = (int) $category->id;
                    break;
                }
            }
        }

        if ($matchedIds === [] && $source->default_category_id) {
            $matchedIds[] = (int) $source->default_category_id;
        }

        if ($matchedIds === []) {
            $firstId = $this->firstCategoryId();
            if ($firstId !== null) {
                $matchedIds[] = $firstId;
            }
        }

        return array_values(array_unique($matchedIds));
    }

    /**
     * Görseli güvenli şekilde indir ve kaydet (SSRF, TLS ve 10 MB boyut sınırı kontrollü).
     */
    protected function downloadAndSaveImage(string $url, string $slug): ?int
    {
        try {
            if (!$this->urlSecurity->isSafePublicUrl($url)) {
                return null;
            }

            $response = Http::timeout(12)
                ->connectTimeout(5)
                ->withOptions($this->urlSecurity->safeRedirectOptions(3))
                ->get($url);

            if (!$response->successful()) {
                return null;
            }

            $contentLength = (int) $response->header('Content-Length');
            if ($contentLength > self::MAX_IMAGE_BYTES) {
                return null;
            }

            $body = $response->body();
            if ($body === '' || strlen($body) > self::MAX_IMAGE_BYTES) {
                return null;
            }

            $contentType = strtolower($response->header('Content-Type'));
            $validMimeTypes = [
                'image/jpeg' => 'jpg',
                'image/jpg'  => 'jpg',
                'image/png'  => 'png',
                'image/gif'  => 'gif',
                'image/webp' => 'webp',
            ];

            $extension = null;
            foreach ($validMimeTypes as $mime => $ext) {
                if (str_contains($contentType, $mime)) {
                    $extension = $ext;
                    break;
                }
            }

            if ($extension === null) {
                return null;
            }

            $fileName = Str::limit(Str::slug($slug), 120, '') . '-' . uniqid() . '.' . $extension;
            $path = 'news-images/' . $fileName;

            Storage::disk('public')->put($path, $body);

            $image = Image::create([
                'path' => $path,
                'name' => $fileName,
            ]);

            return (int) $image->id;
        } catch (\Throwable $e) {
            Log::warning('Image Download Error: ' . $e->getMessage(), ['url' => $url, 'slug' => $slug]);

            return null;
        }
    }

    /**
     * Otomatik etiketleme (doğru usage_count artışı ile).
     *
     * @param list<string> $categories
     */
    protected function autoTag(News $news, string $title, array $categories): void
    {
        $tags = [];

        foreach ($categories as $cat) {
            $cat = trim($cat);
            if ($cat !== '' && mb_strlen($cat, 'UTF-8') <= 50) {
                $tags[] = $cat;
            }
        }

        $commonWords = ['ve', 'bir', 'bu', 'ile', 'için', 'olan', 'den', 'dan', 'de', 'da', 'mi', 'mı', 'gibi', 'daha', 'çok', 'yeni'];
        $cleanWords = preg_replace('/[^\p{L}\p{N}\s]+/u', ' ', $title) ?? $title;
        $words = array_filter(explode(' ', $cleanWords), static function (string $word) use ($commonWords): bool {
            return mb_strlen($word, 'UTF-8') > 3 && !in_array(mb_strtolower($word, 'UTF-8'), $commonWords, true);
        });

        $titleTags = array_slice(array_values($words), 0, 3);
        $tags = array_merge($tags, $titleTags);

        $tagIds = [];
        foreach (array_unique(array_slice($tags, 0, 5)) as $tagName) {
            $tagSlug = Str::slug($tagName);
            if ($tagSlug === '') {
                continue;
            }
            $tag = Tag::firstOrCreate(
                ['slug' => $tagSlug],
                ['name' => $tagName]
            );
            $tagIds[] = (int) $tag->id;
        }

        $tagIds = array_values(array_unique($tagIds));
        if ($tagIds !== []) {
            $changes = $news->tags()->sync($tagIds);
            if (!empty($changes['attached'])) {
                Tag::whereIn('id', $changes['attached'])->increment('usage_count');
            }
        }
    }
}
