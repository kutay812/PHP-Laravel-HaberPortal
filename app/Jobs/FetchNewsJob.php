<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\NewsSource;
use App\Services\RssFetcherService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class FetchNewsJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 300;
    public int $tries = 2;
    public int $maxExceptions = 1;
    public int $uniqueFor = 600;

    public function __construct(
        public readonly ?int $newsSourceId = null,
        public readonly bool $force = true,
    ) {}

    public function uniqueId(): string
    {
        return 'fetch_news_' . ($this->newsSourceId ?? 'all');
    }

    /**
     * @return list<WithoutOverlapping>
     */
    public function middleware(): array
    {
        return [
            (new WithoutOverlapping($this->uniqueId()))->dontRelease()->expireAfter(330),
        ];
    }

    /**
     * @return list<int>
     */
    public function backoff(): array
    {
        return [60, 180];
    }

    public function handle(RssFetcherService $fetcher): void
    {
        if ($this->newsSourceId !== null) {
            $source = NewsSource::find($this->newsSourceId);
            if ($source === null) {
                return;
            }

            $result = $fetcher->fetchFromSource($source);
            Log::info("FetchNewsJob completed for {$source->name}", $result);
        } else {
            $results = $fetcher->fetchAll($this->force);
            Log::info('FetchNewsJob completed for all active sources', ['sources_processed' => count($results)]);
        }

        $this->flushHomepageCaches();
    }

    private function flushHomepageCaches(): void
    {
        foreach ([
            'news.featured.all',
            'news.breaking',
            'news.editor_picks',
            'news.most_read',
            'news.most_commented',
            'tags.trending',
            'seo.sitemap.index',
            'seo.sitemap.news',
            'seo.rss.feed',
        ] as $key) {
            Cache::forget($key);
        }

        foreach (['gundem', 'ekonomi', 'spor', 'dunya', 'teknoloji', 'yasam', 'saglik', 'otomobil', 'kultur'] as $slug) {
            Cache::forget('news.category_block.' . $slug);
            Cache::forget('news.featured.' . $slug);
        }
    }
}
