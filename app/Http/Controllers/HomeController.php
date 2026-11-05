<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\RoleName;
use App\Http\Requests\StoreNewsRequest;
use App\Http\Requests\UpdateNewsRequest;
use App\Models\Category;
use App\Models\News;
use App\Models\Tag;
use App\Providers\AppServiceProvider;
use App\Services\MarketDataService;
use App\Services\NewsService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class HomeController extends Controller
{
    /** @var list<string> */
    private const HOMEPAGE_CATEGORY_SLUGS = [
        'gundem', 'ekonomi', 'spor', 'dunya', 'teknoloji', 'yasam', 'saglik', 'otomobil',
    ];

    public function __construct(
        protected readonly NewsService $newsService,
    ) {}

    /**
     * ANA SAYFA
     */
    public function index(Request $request, MarketDataService $marketDataService): View
    {
        $categories = Cache::remember(
            AppServiceProvider::GLOBAL_CATEGORIES_CACHE_KEY,
            3600,
            static fn () => Category::orderBy('name')->get()
        );

        $categorySlug = $request->query('kategori') ?? $request->route('slug');
        $category = null;
        if (is_string($categorySlug) && $categorySlug !== '') {
            $category = $categories->firstWhere('slug', $categorySlug)
                ?? Category::where('slug', $categorySlug)->first();
        }

        $marketData = $marketDataService->getMarketData();

        $featuredKey = 'news.featured.' . (is_string($categorySlug) && $categorySlug !== '' ? $categorySlug : 'all');
        $featuredNews = Cache::remember($featuredKey, 120, static function () use ($category) {
            $query = News::with(['categories', 'category', 'user', 'image', 'tags', 'newsSource'])
                ->featured()
                ->latest();
            if ($category !== null) {
                $query->where('category_id', $category->id);
            }
            $results = $query->limit(10)->get();

            if ($results->isEmpty()) {
                $fallbackQuery = News::with(['categories', 'category', 'user', 'image', 'tags', 'newsSource'])
                    ->published()
                    ->latest();
                if ($category !== null) {
                    $fallbackQuery->where('category_id', $category->id);
                }
                $results = $fallbackQuery->limit(10)->get();
            }

            return $results;
        });

        $breakingNews = Cache::remember('news.breaking', 60, static function () {
            $results = News::with('category')->breaking()->latest()->limit(10)->get();
            if ($results->isEmpty()) {
                $results = News::with('category')->published()->latest()->limit(10)->get();
            }

            return $results;
        });

        $editorPicks = Cache::remember('news.editor_picks', 300, static function () {
            $results = News::with(['categories', 'category', 'user', 'image'])
                ->editorPicks()
                ->latest()
                ->limit(5)
                ->get();
            if ($results->isEmpty()) {
                $results = News::with(['categories', 'category', 'user', 'image'])
                    ->published()
                    ->latest()
                    ->limit(5)
                    ->get();
            }

            return $results;
        });

        $mostRead = Cache::remember('news.most_read', 300, static function () {
            return News::with(['categories', 'category', 'image'])
                ->published()
                ->where('created_at', '>=', now()->subDays(7))
                ->orderByDesc('views')
                ->limit(10)
                ->get();
        });

        $categoryBlocks = [];
        if ($category === null && !$request->filled('search') && !$request->filled('tag')) {
            foreach (self::HOMEPAGE_CATEGORY_SLUGS as $slug) {
                $categoryBlocks[$slug] = Cache::remember('news.category_block.' . $slug, 180, static function () use ($slug) {
                    return News::with(['categories', 'category', 'image'])
                        ->published()
                        ->whereHas('category', static function (Builder $q) use ($slug): void {
                            $q->where('slug', $slug);
                        })
                        ->latest()
                        ->limit(6)
                        ->get();
                });
            }
        }

        $trendingTags = Cache::remember('tags.trending', 600, static function () {
            return Tag::orderByDesc('usage_count')->limit(15)->get();
        });

        $newsQuery = News::with(['categories', 'category', 'user', 'image', 'tags', 'newsSource'])
            ->published()
            ->latest();

        if ($category !== null) {
            $newsQuery->where('category_id', $category->id);
        }

        if ($request->filled('search')) {
            $search = mb_substr(trim((string) $request->input('search')), 0, 100, 'UTF-8');
            if ($search !== '') {
                $newsQuery->where(static function (Builder $q) use ($search): void {
                    $q->where('title', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            }
        }

        if ($request->filled('tag')) {
            $tagSlug = (string) $request->input('tag');
            $newsQuery->whereHas('tags', static function (Builder $q) use ($tagSlug): void {
                $q->where('slug', $tagSlug);
            });
        }

        $news = $newsQuery->paginate(12)->withQueryString();

        $canEditNews = Auth::check() && Auth::user()?->hasAnyRole(RoleName::staffRoles());

        return view('welcome', compact(
            'categories',
            'news',
            'category',
            'canEditNews',
            'featuredNews',
            'breakingNews',
            'editorPicks',
            'mostRead',
            'trendingTags',
            'categoryBlocks',
            'marketData'
        ));
    }

    /**
     * HABER DETAY SAYFASI
     */
    public function show(string $slug): View
    {
        $canPreviewDrafts = Auth::check() && Auth::user()?->hasAnyRole(RoleName::staffRoles());

        $news = News::with(['categories', 'category', 'user', 'image', 'tags', 'comments.user', 'newsSource'])
            ->where('slug', $slug)
            ->when(!$canPreviewDrafts, static fn (Builder $q): Builder => $q->published())
            ->firstOrFail();

        $sessionKey = 'viewed_news_' . $news->id;
        if (!session()->has($sessionKey)) {
            News::whereKey($news->id)->increment('views');
            $news->views = ((int) $news->views) + 1;
            session()->put($sessionKey, true);
        }

        $relatedNews = $news->relatedNews(4);

        $mostRead = News::with(['category', 'image'])
            ->published()
            ->where('created_at', '>=', now()->subDays(7))
            ->orderByDesc('views')
            ->limit(5)
            ->get();

        $prevNews = News::published()
            ->where('id', '<', $news->id)
            ->orderByDesc('id')
            ->first();

        $nextNews = News::published()
            ->where('id', '>', $news->id)
            ->orderBy('id')
            ->first();

        $categoryNews = News::with(['image'])
            ->published()
            ->where('category_id', $news->category_id)
            ->where('id', '!=', $news->id)
            ->latest()
            ->limit(4)
            ->get();

        return view('news.show', compact(
            'news',
            'relatedNews',
            'prevNews',
            'nextNews',
            'categoryNews',
            'mostRead'
        ));
    }

    /**
     * HABER KAYDETME (STORE)
     */
    public function store(StoreNewsRequest $request): RedirectResponse
    {
        $this->newsService->createNews($request->validated(), (int) auth()->id());
        $this->clearNewsCache();

        return redirect()->route('home')->with('success', 'Haber başarıyla eklendi!');
    }

    /**
     * HABER GÜNCELLEME (UPDATE)
     */
    public function update(UpdateNewsRequest $request, int|string $id): RedirectResponse
    {
        $haber = News::findOrFail($id);
        $this->newsService->updateNews($haber, $request->validated());
        $this->clearNewsCache();

        return redirect()->route('news.show', $haber->slug)->with('success', 'Haber başarıyla güncellendi.');
    }

    protected function clearNewsCache(): void
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

        foreach (self::HOMEPAGE_CATEGORY_SLUGS as $slug) {
            Cache::forget('news.category_block.' . $slug);
            Cache::forget('news.featured.' . $slug);
        }
    }
}
