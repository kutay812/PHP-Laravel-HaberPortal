<?php

declare(strict_types=1);

namespace App\Providers;

use App\Enums\RoleName;
use App\Models\Category;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public const GLOBAL_CATEGORIES_CACHE_KEY = 'categories.all';
    public const GLOBAL_SETTINGS_CACHE_KEY = 'global_settings';

    /**
     * Register any application services.
     */
    public function register(): void
    {
        if (
            $this->app->environment('local')
            && class_exists(\Laravel\Telescope\TelescopeApplicationServiceProvider::class)
            && class_exists(TelescopeServiceProvider::class)
        ) {
            $this->app->register(TelescopeServiceProvider::class);
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        Schema::defaultStringLength(191);

        Gate::before(static function (User $user, string $ability): ?bool {
            return $user->hasRole(RoleName::SuperAdmin->value) ? true : null;
        });

        $this->configureRateLimiters();

        // Global verileri önbellek ile view'lerle paylaş
        if (!$this->app->runningInConsole()) {
            try {
                $globalCategories = Cache::remember(
                    self::GLOBAL_CATEGORIES_CACHE_KEY,
                    3600,
                    static fn () => Category::orderBy('name')->get()
                );
                view()->share('globalCategories', $globalCategories);

                $siteSettings = Cache::remember(
                    self::GLOBAL_SETTINGS_CACHE_KEY,
                    3600,
                    static fn (): array => [
                        'siteName'            => Setting::get('site_name', 'HaberPortal'),
                        'siteDesc'            => Setting::get('site_description', 'En güncel ve doğru haberler'),
                        'breakingNewsEnabled' => Setting::get('breaking_news_enabled', true),
                    ]
                );

                view()->share('siteName', $siteSettings['siteName']);
                view()->share('siteDesc', $siteSettings['siteDesc']);
                view()->share('breakingNewsEnabled', $siteSettings['breakingNewsEnabled']);
            } catch (\Throwable) {
                // Kurulum veya migration esnasındaki hataları yoksay
            }
        }
    }

    /**
     * Configure dual-key (User ID + IP) rate limiters for all cost/load sensitive endpoints.
     */
    private function configureRateLimiters(): void
    {
        $dualLimit = static function (Request $request, int $perMinute, string $prefix): array {
            $ip = (string) ($request->ip() ?? 'unknown');
            $limits = [
                Limit::perMinute($perMinute)->by("{$prefix}:ip:{$ip}"),
            ];

            $userId = $request->user()?->getAuthIdentifier();
            if ($userId !== null) {
                $limits[] = Limit::perMinute($perMinute)->by("{$prefix}:user:{$userId}");
            }

            return $limits;
        };

        RateLimiter::for('rss-fetch', static fn (Request $request): array => $dualLimit(
            $request,
            max(1, (int) config('security.rate_limits.rss_fetch_per_minute', 2)),
            'rss-fetch'
        ));

        RateLimiter::for('news-write', static fn (Request $request): array => $dualLimit(
            $request,
            max(1, (int) config('security.rate_limits.news_write_per_minute', 10)),
            'news-write'
        ));

        RateLimiter::for('comments-write', static fn (Request $request): array => $dualLimit(
            $request,
            max(1, (int) config('security.rate_limits.comment_write_per_minute', 5)),
            'comments-write'
        ));

        RateLimiter::for('profile-write', static fn (Request $request): array => $dualLimit(
            $request,
            max(1, (int) config('security.rate_limits.profile_write_per_minute', 6)),
            'profile-write'
        ));

        RateLimiter::for('public-browse', static function (Request $request) use ($dualLimit): array {
            if ($request->filled('search')) {
                return $dualLimit(
                    $request,
                    max(1, (int) config('security.rate_limits.public_search_per_minute', 15)),
                    'public-search'
                );
            }

            return $dualLimit(
                $request,
                max(1, (int) config('security.rate_limits.public_browse_per_minute', 90)),
                'public-browse'
            );
        });

        RateLimiter::for('admin-search', static fn (Request $request): array => $dualLimit(
            $request,
            max(1, (int) config('security.rate_limits.admin_search_per_minute', 30)),
            'admin-search'
        ));

        RateLimiter::for('xml-feeds', static fn (Request $request): array => $dualLimit(
            $request,
            max(1, (int) config('security.rate_limits.xml_feeds_per_minute', 20)),
            'xml-feeds'
        ));

        RateLimiter::for('api', static fn (Request $request): array => $dualLimit(
            $request,
            max(1, (int) config('security.rate_limits.api_per_minute', 60)),
            'api'
        ));
    }
}
