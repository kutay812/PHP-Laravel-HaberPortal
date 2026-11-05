<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Str;

class TurkishArticleRewriterService
{
    /**
     * Case-governed Turkish journalistic collocation & predicate replacements.
     * Every source expression and its replacement share identical grammatical case governance
     * so preceding Turkish suffixes (-ı/-i, -a/-e, -dan/-den, -dığını, -dığı) remain 100% natural.
     *
     * @var array<string, string>
     */
    private const PHRASE_MAP = [
        // Complement clause predicates (-dığını / -dığı)
        'olduğunu vurguladı'            => 'olduğunun altını çizdi',
        'olduğunu belirtti'             => 'olduğunu dile getirdi',
        'olduğunu ifade etti'           => 'olduğunu kaydetti',
        'olduğunu söyledi'              => 'olduğunu ifade etti',
        'olduğunu açıkladı'             => 'olduğunu duyurdu',
        'olduğunu bildirdi'             => 'olduğunu aktardı',
        'olduğunu dile getirdi'         => 'olduğunu belirtti',
        'olduğu belirtildi'             => 'olduğu kaydedildi',
        'olduğu ifade edildi'           => 'olduğu aktarıldı',
        'olduğu bildirildi'             => 'olduğu paylaşıldı',
        'olduğu öğrenildi'              => 'olduğu bilgisine ulaşıldı',
        'olduğu açıklandı'              => 'olduğu duyuruldu',
        'olduğu vurgulandı'             => 'olduğuna dikkat çekildi',

        // Reporting & statement predicates
        'açıklamalarda bulundu'         => 'değerlendirmeler yaptı',
        'açıklamada bulundu'            => 'değerlendirmede bulundu',
        'açıklama yapıldı'              => 'bilgilendirme yapıldı',
        'yazılı açıklama yaptı'         => 'yazılı bilgilendirmede bulundu',
        'ifade etti'                    => 'dile getirdi',
        'ifade edildi'                  => 'kaydedildi',
        'ifade ederek'                  => 'dile getirerek',
        'dikkat çekti'                  => 'vurgu yaptı',
        'dikkat çekildi'                => 'vurgu yapıldı',
        'dikkat çekerek'                => 'vurgu yaparak',
        'bilgi verdi'                   => 'bilgilendirmede bulundu',
        'bilgi paylaşıldı'              => 'ayrıntılar aktarıldı',
        'değerlendirmesinde bulundu'    => 'değerlendirmesini paylaştı',
        'ziyarette bulundu'             => 'ziyaret gerçekleştirdi',
        'incelemelerde bulundu'         => 'saha incelemesi yaptı',
        'bir araya geldi'               => 'görüşme gerçekleştirdi',
        'bir araya gelerek'             => 'buluşarak',
        'görüş alışverişinde bulundu'   => 'istişare yaptı',

        // Action & event predicates
        'hayata geçirildi'              => 'uygulamaya alındı',
        'hayata geçirdi'                => 'uygulamaya koydu',
        'hayata geçirilecek'            => 'uygulamaya alınacak',
        'gerçekleştirildi'              => 'yapıldı',
        'gerçekleştirilen'              => 'düzenlenen',
        'gerçekleştirilecek'            => 'düzenlenecek',
        'gerçekleşti'                   => 'yaşandı',
        'ele alındı'                    => 'değerlendirildi',
        'ele alındı.'                   => 'masaya yatırıldı.',
        'meydana geldi'                 => 'yaşandı',
        'meydana gelen'                 => 'yaşanan',
        'devreye alındı'                => 'uygulamaya konuldu',
        'devreye girdi'                 => 'faaliyete geçti',
        'hizmete açıldı'                => 'kullanıma sunuldu',
        'sona erdi'                     => 'tamamlandı',
        'son buldu'                     => 'tamamlandı',
        'artış gösterdi'                => 'yükseliş kaydetti',
        'düşüş gösterdi'                => 'gerileme kaydetti',
        'büyük önem taşıyor'            => 'kritik değer taşıyor',
        'yakından takip ediliyor'       => 'titizlikle izleniyor',
        'gündeme geldi'                 => 'öne çıktı',
        'hız kesmeden devam ediyor'     => 'aralıksız sürüyor',
        'halen devam ediyor'            => 'kesintisiz sürüyor',

        // Discourse connectors & adverbial transitions
        'öte yandan'                    => 'diğer taraftan',
        'bunun yanı sıra'               => 'bununla birlikte',
        'bununla beraber'               => 'aynı zamanda',
        'ilk etapta'                    => 'başlangıç aşamasında',
        'söz konusu'                    => 'bahsi geçen',
        'konuya ilişkin'                => 'konuyla ilgili',
        'bu kapsamda'                   => 'bu çerçevede',
        'kapsamında'                    => 'çerçevesinde',
        'doğrultusunda'                 => 'ışığında',
        'nedeniyle'                     => 'sebebiyle',
        'ardından'                      => 'sonrasında',
        'yaklaşık olarak'               => 'ortalama',
        'en kısa sürede'                => 'ivedilikle',
        'resmi olarak'                  => 'resmen',
        'eş zamanlı olarak'             => 'aynı anda',
        'genel itibarıyla'              => 'genel anlamda',
        'özellikle dikkat çekiyor'      => 'bilhassa öne çıkıyor',
    ];

    /**
     * Inflected morphological patterns safe across vowel harmony.
     *
     * @var array<string, string>
     */
    private const MORPHOLOGICAL_PATTERNS = [
        '/\b([\p{L}]+)maya devam ediyor\b/u'   => '$1mayı sürdürüyor',
        '/\b([\p{L}]+)meye devam ediyor\b/u'   => '$1meyi sürdürüyor',
        '/\b([\p{L}]+)maya devam etti\b/u'     => '$1mayı sürdürdü',
        '/\b([\p{L}]+)meye devam etti\b/u'     => '$1meyi sürdürdü',
        '/\b([\p{L}]+)masına karar verildi\b/u' => '$1ması kararlaştırıldı',
        '/\b([\p{L}]+)mesine karar verildi\b/u' => '$1mesi kararlaştırıldı',
    ];

    public function __construct(
        private readonly HtmlSanitizerService $sanitizer,
    ) {}

    /**
     * Rewrite, deduplicate, structure, and sanitize a Turkish news article without
     * altering facts, numbers, dates, proper nouns, or direct quotations.
     *
     * @return array{content: string, description: string, is_enriched: bool}
     */
    public function rewrite(string $title, string $rawContent, string $rawDescription = ''): array
    {
        $cleanTitle = $this->cleanText($title);
        $cleanDesc = $this->cleanAgencyPrefix($this->cleanText($rawDescription));
        $cleanBody = $this->cleanAgencyPrefix($this->extractTextBlocks($rawContent));

        if ($cleanBody === '' && $cleanDesc !== '') {
            $cleanBody = $cleanDesc;
        }

        // Merge description ahead of body if body does not already include it
        if (
            $cleanDesc !== ''
            && mb_strlen($cleanDesc, 'UTF-8') >= 45
            && !str_contains(
                mb_strtolower(mb_substr($cleanBody, 0, 320, 'UTF-8'), 'UTF-8'),
                mb_strtolower(mb_substr($cleanDesc, 0, 40, 'UTF-8'), 'UTF-8')
            )
        ) {
            $cleanBody = $cleanDesc . "\n\n" . $cleanBody;
        }

        $sentences = $this->splitAndDeduplicateSentences($cleanBody, $cleanTitle);

        if ($sentences === []) {
            $fallback = $this->sanitizer->sanitize('<p>' . e($cleanTitle) . '</p>');

            return [
                'content'     => $fallback,
                'description' => Str::limit($cleanTitle, 280),
                'is_enriched' => false,
            ];
        }

        $rewrittenSentences = array_map(
            fn (string $sentence): string => $this->paraphraseSentence($sentence),
            $sentences
        );

        $structuredHtml = $this->buildEditorialHtml($cleanTitle, $rewrittenSentences);
        $sanitizedHtml = $this->sanitizer->sanitize($structuredHtml);
        $summary = $this->buildEditorialSummary($rewrittenSentences, $cleanDesc, $cleanTitle);

        $plainLength = mb_strlen(strip_tags($sanitizedHtml), 'UTF-8');

        return [
            'content'     => $sanitizedHtml,
            'description' => $summary,
            'is_enriched' => $plainLength >= 350,
        ];
    }

    /**
     * Paraphrase a single sentence while shielding direct quotes, URLs, and numeric expressions.
     */
    public function paraphraseSentence(string $sentence): string
    {
        $trimmed = trim($sentence);
        if ($trimmed === '') {
            return '';
        }

        $vault = [];
        $shielded = $this->shieldProtectedSpans($trimmed, $vault);

        // Apply morphological patterns first
        foreach (self::MORPHOLOGICAL_PATTERNS as $pattern => $replacement) {
            $shielded = preg_replace($pattern, $replacement, $shielded) ?? $shielded;
        }

        // Apply multi-word phrase replacements with Turkish case preservation
        foreach (self::PHRASE_MAP as $source => $target) {
            $regex = '/(?<![\p{L}\p{N}_])' . preg_quote($source, '/') . '(?![\p{L}\p{N}_])/iu';
            $shielded = preg_replace_callback(
                $regex,
                fn (array $match): string => $this->matchTurkishCase($match[0], $target),
                $shielded
            ) ?? $shielded;
        }

        // Restore protected quotes, numbers, and URLs verbatim
        if ($vault !== []) {
            $shielded = strtr($shielded, $vault);
        }

        return trim(preg_replace('/\s{2,}/u', ' ', $shielded) ?? $shielded);
    }

    /**
     * Shield direct quotations, URLs, percentages, currencies, and measurements from modification.
     *
     * @param array<string, string> $vault
     */
    private function shieldProtectedSpans(string $text, array &$vault): string
    {
        $patterns = [
            // Direct quotes: "...", “...”, «...»
            '/"[^"\n]{3,}"/u',
            '/“[^”\n]{3,}”/u',
            '/«[^»\n]{3,}»/u',
            // URLs & emails
            '/https?:\/\/\S+/iu',
            '/[\w.+-]+@[\w-]+\.[\w.-]+/iu',
            // Percentages, numbers with decimals/units/currencies
            '/(?:%\s*\d+(?:[.,]\d+)?|\d+(?:[.,]\d+)+\s*(?:TL|dolar|euro|sterlin|milyar|milyon|bin|km|metre|ton|kg)?)/iu',
        ];

        $counter = 0;
        foreach ($patterns as $pattern) {
            $text = preg_replace_callback($pattern, static function (array $m) use (&$vault, &$counter): string {
                $token = '__SHIELD_' . ($counter++) . '__';
                $vault[$token] = $m[0];

                return $token;
            }, $text) ?? $text;
        }

        return $text;
    }

    private function matchTurkishCase(string $original, string $replacement): string
    {
        $firstChar = mb_substr($original, 0, 1, 'UTF-8');
        $upperFirst = $this->turkishUpper($firstChar);

        if ($firstChar === $upperFirst && $firstChar !== $this->turkishLower($firstChar)) {
            $repFirst = mb_substr($replacement, 0, 1, 'UTF-8');
            $repRest = mb_substr($replacement, 1, null, 'UTF-8');

            return $this->turkishUpper($repFirst) . $repRest;
        }

        return $replacement;
    }

    private function turkishUpper(string $value): string
    {
        $mapped = str_replace(['i', 'ı'], ['İ', 'I'], $value);

        return mb_strtoupper($mapped, 'UTF-8');
    }

    private function turkishLower(string $value): string
    {
        $mapped = str_replace(['İ', 'I'], ['i', 'ı'], $value);

        return mb_strtolower($mapped, 'UTF-8');
    }

    /**
     * @param list<string> $sentences
     */
    private function buildEditorialHtml(string $title, array $sentences): string
    {
        $count = count($sentences);
        if ($count === 1) {
            return '<p>' . e($sentences[0]) . '</p>';
        }

        $blocks = [];

        // Lead / spot paragraph (strong journalistic opening)
        $leadSentenceCount = ($count >= 4 && mb_strlen($sentences[0], 'UTF-8') < 130) ? 2 : 1;
        $leadText = implode(' ', array_slice($sentences, 0, $leadSentenceCount));
        $blocks[] = '<p><strong>' . e($leadText) . '</strong></p>';

        $remaining = array_slice($sentences, $leadSentenceCount);
        $paragraphs = $this->groupSentencesIntoParagraphs($remaining);

        $subheadings = $this->resolveContextualSubheadings($title . ' ' . $leadText);
        $totalParagraphs = count($paragraphs);

        foreach ($paragraphs as $index => $paragraph) {
            if ($totalParagraphs >= 4 && $index === 1) {
                $blocks[] = '<h2>' . e($subheadings[0]) . '</h2>';
            } elseif ($totalParagraphs >= 6 && $index === 4) {
                $blocks[] = '<h2>' . e($subheadings[1]) . '</h2>';
            }

            $blocks[] = '<p>' . e($paragraph) . '</p>';
        }

        return implode("\n", $blocks);
    }

    /**
     * @param list<string> $sentences
     * @return list<string>
     */
    private function groupSentencesIntoParagraphs(array $sentences): array
    {
        $paragraphs = [];
        $buffer = [];
        $bufferLen = 0;

        foreach ($sentences as $sentence) {
            $buffer[] = $sentence;
            $bufferLen += mb_strlen($sentence, 'UTF-8');

            if (count($buffer) >= 3 || ($bufferLen >= 260 && count($buffer) >= 2)) {
                $paragraphs[] = implode(' ', $buffer);
                $buffer = [];
                $bufferLen = 0;
            }
        }

        if ($buffer !== []) {
            $paragraphs[] = implode(' ', $buffer);
        }

        return $paragraphs;
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function resolveContextualSubheadings(string $context): array
    {
        $lower = $this->turkishLower($context);

        return match (true) {
            (bool) preg_match('/\b(ekonomi|enflasyon|faiz|merkez bankas[ıi]|borsa|dolar|ihracat|bütçe|vergi)\b/u', $lower)
                => ['Ekonomik Veriler ve Piyasa Yansımaları', 'Sürece İlişkin Beklentiler'],
            (bool) preg_match('/\b(maç|futbol|basketbol|lig|transfer|teknik direktör|şampiyon|milli tak[ıi]m)\b/u', $lower)
                => ['Karşılaşma ve Takım Detayları', 'Teknik Heyet ve Sezon Görünümü'],
            (bool) preg_match('/\b(yapay zeka|teknoloji|yaz[ıi]l[ıi]m|siber|uzay|bilim|uygulama|cihaz)\b/u', $lower)
                => ['Teknolojik Yenilik ve Öne Çıkan Özellikler', 'Sektörel Etkiler ve Yol Haritası'],
            (bool) preg_match('/\b(mahkeme|soruşturma|emniyet|operasyon|savc[ıi]l[ıi]k|gözalt[ıi]|adliye)\b/u', $lower)
                => ['Adli Süreç ve Soruşturmanın Ayrıntıları', 'Resmi Makamlardan Gelen Bilgiler'],
            (bool) preg_match('/\b(sağl[ıi]k|hastane|doktor|tedavi|uzman|beslenme|hastalık)\b/u', $lower)
                => ['Uzman Değerlendirmeleri ve Kritik Uyarılar', 'Korunma ve Uygulama Önerileri'],
            default
                => ['Gelişmenin Ayrıntıları', 'Sürece İlişkin Değerlendirmeler'],
        };
    }

    /**
     * @param list<string> $rewrittenSentences
     */
    private function buildEditorialSummary(array $rewrittenSentences, string $cleanDesc, string $title): string
    {
        $candidate = $rewrittenSentences[0] ?? '';
        if (mb_strlen($candidate, 'UTF-8') < 100 && isset($rewrittenSentences[1])) {
            $candidate .= ' ' . $rewrittenSentences[1];
        }

        if (mb_strlen($candidate, 'UTF-8') < 40 && $cleanDesc !== '') {
            $candidate = $this->paraphraseSentence($cleanDesc);
        }

        if (mb_strlen($candidate, 'UTF-8') < 25) {
            $candidate = $title . ' gelişmesine ilişkin güncel ayrıntılar ve öne çıkan başlıklar haberimizde yer alıyor.';
        }

        return Str::limit(trim($candidate), 290, '...');
    }

    /**
     * Split prose into clean sentences without breaking Turkish abbreviations or ordinal numbers.
     *
     * @return list<string>
     */
    private function splitAndDeduplicateSentences(string $text, string $title): array
    {
        if ($text === '') {
            return [];
        }

        // Protect dots in common Turkish abbreviations & ordinals
        $protected = preg_replace(
            '/\b(Prof|Doç|Dr|Av|Alb|Yrb|Org|Gen|Bşk|Müh|Mim|Ecz|Vet|No|Cad|Sok|Mah|Apt|Bul|Hz|St|vb|vs|yy)\./iu',
            '$1__DOT__',
            $text
        ) ?? $text;
        $protected = preg_replace('/\b(\d{1,4})\.\s+(?=[\p{Ll}])/u', '$1__DOT__ ', $protected) ?? $protected;

        $paragraphs = preg_split('/\n{2,}/u', $protected) ?: [$protected];
        $sentences = [];
        $seen = [];
        $normTitle = $this->turkishLower(trim($title));

        foreach ($paragraphs as $para) {
            $parts = preg_split('/(?<=[.!?…])\s+(?=[\p{Lu}"“«0-9])/u', trim($para)) ?: [trim($para)];
            foreach ($parts as $part) {
                $restored = trim(str_replace('__DOT__', '.', $part));
                $restored = preg_replace('/\s+/u', ' ', $restored) ?? $restored;

                if (mb_strlen($restored, 'UTF-8') < 18) {
                    continue;
                }

                if ($this->isNoiseSentence($restored)) {
                    continue;
                }

                // Ensure terminal punctuation
                if (!preg_match('/[.!?…""”»]$/u', $restored)) {
                    $restored .= '.';
                }

                $fingerprint = preg_replace('/[^\p{L}\p{N}]+/u', '', $this->turkishLower($restored)) ?? '';
                if ($fingerprint === '' || isset($seen[$fingerprint])) {
                    continue;
                }

                $titleFingerprint = preg_replace('/[^\p{L}\p{N}]+/u', '', $normTitle) ?? '';
                if ($titleFingerprint !== '' && $fingerprint === $titleFingerprint) {
                    continue;
                }

                $seen[$fingerprint] = true;
                $sentences[] = $restored;
            }
        }

        return $this->collapseNearDuplicateSentences($sentences);
    }

    /**
     * If an opening teaser sentence is an 80%+ token subset of the immediately following fuller sentence,
     * keep the richer sentence to avoid repetitive lead openings.
     *
     * @param list<string> $sentences
     * @return list<string>
     */
    private function collapseNearDuplicateSentences(array $sentences): array
    {
        $result = [];
        $count = count($sentences);

        for ($i = 0; $i < $count; $i++) {
            $current = $sentences[$i];
            if ($i + 1 < $count) {
                $next = $sentences[$i + 1];
                if ($this->isSubsetSentence($current, $next)) {
                    continue;
                }
            }
            if ($result !== [] && $this->isSubsetSentence($current, $result[count($result) - 1])) {
                continue;
            }
            $result[] = $current;
        }

        return $result;
    }

    private function isSubsetSentence(string $shorter, string $longer): bool
    {
        if (mb_strlen($shorter, 'UTF-8') >= mb_strlen($longer, 'UTF-8')) {
            return false;
        }

        preg_match_all('/[\p{L}\p{N}]{3,}/u', $this->turkishLower($shorter), $shortMatches);
        preg_match_all('/[\p{L}\p{N}]{3,}/u', $this->turkishLower($longer), $longMatches);

        $shortTokens = array_unique($shortMatches[0]);
        if (count($shortTokens) < 3) {
            return false;
        }

        $longText = ' ' . implode(' ', $longMatches[0]) . ' ';
        $matched = 0;
        foreach ($shortTokens as $token) {
            $stem = mb_substr($token, 0, max(4, (int) floor(mb_strlen($token, 'UTF-8') * 0.75)), 'UTF-8');
            if (str_contains($longText, ' ' . $stem)) {
                $matched++;
            }
        }

        return ($matched / count($shortTokens)) >= 0.75;
    }

    private function isNoiseSentence(string $sentence): bool
    {
        return (bool) preg_match(
            '/(google\s+news|abone\s+ol|haberin\s+devam[ıi]|bizi\s+takip\s+edin|tüm\s+haklar[ıi]\s+sakl[ıi]d[ıi]r|telif\s+hakk[ıi]|iktibas\s+edilemez|internet\s+sitesinde\s+yay[ıi]nlanan|kaynak\s+gösterilerek\s+dahi|emoji\s+ile\s+tepki|izinsiz\s+kopyalanamaz|whatsapp\s+ihbar|tıklayınız|uygulamam[ıi]z[ıi]\s+indirin)/iu',
            $sentence
        );
    }

    private function extractTextBlocks(string $htmlOrText): string
    {
        if ($htmlOrText === '') {
            return '';
        }

        // Convert block closing tags to paragraph breaks before stripping HTML
        $withBreaks = preg_replace('/<\/(p|div|h[1-6]|li|blockquote|article|section)>|<br\s*\/?>/i', "\n\n", $htmlOrText) ?? $htmlOrText;

        return $this->cleanText($withBreaks);
    }

    private function cleanText(string $text): string
    {
        $decoded = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $decoded = str_replace(["\r\n", "\r", "\xC2\xA0"], ["\n", "\n", ' '], $decoded);
        $lines = array_map(
            static fn (string $l): string => trim(preg_replace('/[ \t]+/u', ' ', $l) ?? $l),
            explode("\n", $decoded)
        );
        $lines = array_values(array_filter($lines, static fn (string $l): bool => $l !== ''));

        return implode("\n\n", $lines);
    }

    private function cleanAgencyPrefix(string $text): string
    {
        if ($text === '') {
            return '';
        }

        // Strip leading city/agency datelines like "ANKARA (AA) - ", "İSTANBUL (DHA) - ", "AA - "
        $cleaned = preg_replace(
            '/^(?:[\p{Lu}\s]{2,25}\s*)?(?:\((?:AA|İHA|DHA|ANKA|TRT|REUTERS|AFP|AP)\)|(?:AA|İHA|DHA|ANKA|TRT\s*Haber))\s*[-–—:|\/]\s*/u',
            '',
            $text
        ) ?? $text;

        return trim($cleaned);
    }
}
