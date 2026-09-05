<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->app->environment('production')) {
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }

        Schema::defaultStringLength(191);

        // Global verileri önbellek ile view'lerle paylaş
        if (!app()->runningInConsole()) {
            try {
                $globalCategories = \Illuminate\Support\Facades\Cache::remember('global_categories', 3600, function () {
                    return \App\Models\Category::orderBy('name')->get();
                });
                view()->share('globalCategories', $globalCategories);

                $siteSettings = \Illuminate\Support\Facades\Cache::remember('global_settings', 3600, function () {
                    return [
                        'siteName'            => \App\Models\Setting::get('site_name', 'HaberPortal'),
                        'siteDesc'            => \App\Models\Setting::get('site_description', 'En güncel ve doğru haberler'),
                        'breakingNewsEnabled' => \App\Models\Setting::get('breaking_news_enabled', true),
                    ];
                });

                view()->share('siteName', $siteSettings['siteName']);
                view()->share('siteDesc', $siteSettings['siteDesc']);
                view()->share('breakingNewsEnabled', $siteSettings['breakingNewsEnabled']);
            } catch (\Throwable $e) {
                // Kurulum veya migration esnasındaki hataları yoksay
            }
        }
    }
}