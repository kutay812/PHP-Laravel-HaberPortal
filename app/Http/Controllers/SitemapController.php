<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\News;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

class SitemapController extends Controller
{
    /**
     * Standard XML Sitemap (cached 10 minutes to prevent scraper DB overload)
     */
    public function index(): Response
    {
        $content = Cache::remember('seo.sitemap.index', 600, static function (): string {
            $news = News::published()->latest()->limit(500)->get();
            $categories = Category::all();

            return view('seo.sitemap', compact('news', 'categories'))->render();
        });

        return response($content, 200)
            ->header('Content-Type', 'text/xml; charset=UTF-8');
    }

    /**
     * Google News XML Sitemap (cached 10 minutes)
     */
    public function news(): Response
    {
        $content = Cache::remember('seo.sitemap.news', 600, static function (): string {
            $news = News::published()
                ->with('newsSource')
                ->where('created_at', '>=', now()->subHours(48))
                ->latest()
                ->limit(1000)
                ->get();

            return view('seo.sitemap-news', compact('news'))->render();
        });

        return response($content, 200)
            ->header('Content-Type', 'text/xml; charset=UTF-8');
    }

    /**
     * RSS Feed for aggregators (eager-loaded + cached 5 minutes)
     */
    public function feed(): Response
    {
        $content = Cache::remember('seo.rss.feed', 300, static function (): string {
            $news = News::with(['category', 'user', 'image', 'newsSource'])
                ->published()
                ->latest()
                ->limit(50)
                ->get();

            return view('seo.rss', compact('news'))->render();
        });

        return response($content, 200)
            ->header('Content-Type', 'text/xml; charset=UTF-8');
    }
}
