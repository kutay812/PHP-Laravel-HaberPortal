<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\News;
use App\Services\ArticleExtractorService;
use App\Services\TurkishArticleRewriterService;
use App\Services\UrlSecurityService;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class EnrichNews extends Command
{
    protected $signature = 'news:enrich
                            {--limit=0 : İşlenecek maksimum haber sayısı (0 = tümü)}
                            {--chunk=15 : Eşzamanlı HTTP havuzu boyutu}
                            {--min-length=450 : Bu karakter sayısından kısa içerikleri kaynak URL üzerinden zenginleştir}
                            {--all : Uzun haberleri de yerel özgünleştirme ve HTML yapılandırma motorundan geçir}';

    protected $description = 'Kısa/özet RSS haberlerinin kaynak sayfasından tam metnini çeker ve yerel Türkçe özgünleştirme motoruyla yeniden yapılandırır';

    public function handle(
        ArticleExtractorService $extractor,
        TurkishArticleRewriterService $rewriter,
        UrlSecurityService $urlSecurity,
    ): int {
        $limit = max(0, (int) $this->option('limit'));
        $chunkSize = max(1, min(30, (int) $this->option('chunk')));
        $minLength = max(100, (int) $this->option('min-length'));
        $processAll = (bool) $this->option('all');

        $query = News::query()->orderByDesc('id');
        if (!$processAll) {
            $lengthFn = DB::connection()->getDriverName() === 'sqlite' ? 'LENGTH' : 'CHAR_LENGTH';
            $query->whereRaw("{$lengthFn}(COALESCE(content, '')) < ?", [$minLength]);
        }
        if ($limit > 0) {
            $query->limit($limit);
        }

        /** @var Collection<int, News> $articles */
        $articles = $query->get();

        if ($articles->isEmpty()) {
            $this->info('Zenginleştirilecek kısa haber bulunamadı.');

            return self::SUCCESS;
        }

        $total = $articles->count();
        $this->info("Toplam {$total} haber işlenecek (eşzamanlı havuz: {$chunkSize})...");

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $stats = [
            'enriched_from_web' => 0,
            'rewritten_local'   => 0,
            'unchanged'         => 0,
        ];

        foreach ($articles->chunk($chunkSize) as $batch) {
            $fetchTargets = [];
            foreach ($batch as $news) {
                $plainLen = mb_strlen(trim(strip_tags((string) $news->content)), 'UTF-8');
                $url = trim((string) ($news->source_url ?? ''));
                if ($plainLen < $minLength && $url !== '' && $urlSecurity->isSafePublicUrl($url)) {
                    $fetchTargets[(string) $news->id] = $url;
                }
            }

            $responses = [];
            if ($fetchTargets !== []) {
                $responses = Http::pool(static function (Pool $pool) use ($fetchTargets): array {
                    $requests = [];
                    foreach ($fetchTargets as $key => $url) {
                        $requests[$key] = $pool->as((string) $key)
                            ->timeout(9)
                            ->connectTimeout(4)
                            ->maxRedirects(4)
                            ->withHeaders([
                                'User-Agent'      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36',
                                'Accept'          => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                                'Accept-Language' => 'tr-TR,tr;q=0.9,en-US;q=0.7,en;q=0.6',
                            ])
                            ->get($url);
                    }

                    return $requests;
                });
            }

            foreach ($batch as $news) {
                $rawContent = (string) ($news->content ?? '');
                $rawDescription = (string) ($news->description ?? '');
                $currentPlainLen = mb_strlen(trim(strip_tags($rawContent)), 'UTF-8');
                $webEnriched = false;

                $key = (string) $news->id;
                if (isset($responses[$key]) && $responses[$key] instanceof Response) {
                    $extracted = $extractor->extractFromResponse($responses[$key], $news->title);
                    if (mb_strlen($extracted['body'], 'UTF-8') > $currentPlainLen + 40) {
                        $rawContent = $extracted['body'];
                        $webEnriched = true;
                    }
                    if ($rawDescription === '' && $extracted['description'] !== '') {
                        $rawDescription = $extracted['description'];
                    }
                }

                $rewritten = $rewriter->rewrite((string) $news->title, $rawContent, $rawDescription);

                if ($rewritten['content'] !== '' && $rewritten['content'] !== $news->content) {
                    $newHash = News::generateContentHash((string) $news->title, $rewritten['content']);
                    $duplicateExists = News::withTrashed()
                        ->where('content_hash', $newHash)
                        ->where('id', '!=', $news->id)
                        ->exists();

                    $news->content = $rewritten['content'];
                    $news->description = Str::limit($rewritten['description'], 300, '');
                    $news->meta_description = Str::limit($rewritten['description'], 300, '');
                    $news->reading_time = News::calculateReadingTime($rewritten['content']);
                    if (!$duplicateExists) {
                        $news->content_hash = $newHash;
                    }
                    $news->saveQuietly();

                    if ($webEnriched) {
                        $stats['enriched_from_web']++;
                    } else {
                        $stats['rewritten_local']++;
                    }
                } else {
                    $stats['unchanged']++;
                }

                $bar->advance();
            }
        }

        $bar->finish();
        $this->newLine(2);

        Cache::flush();

        $this->table(
            ['Metrik', 'Adet'],
            [
                ['Kaynak URL Tam Metin + Özgünleştirilen', $stats['enriched_from_web']],
                ['Mevcut Metin Üzerinden Özgünleştirilen / Sanitize Edilen', $stats['rewritten_local']],
                ['Değişmeyen', $stats['unchanged']],
                ['Toplam İşlenen', $total],
            ]
        );

        return self::SUCCESS;
    }
}
