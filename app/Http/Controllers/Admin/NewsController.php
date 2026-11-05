<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreNewsRequest;
use App\Http\Requests\UpdateNewsRequest;
use App\Models\Category;
use App\Models\News;
use App\Services\NewsService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class NewsController extends Controller
{
    public function __construct(
        protected readonly NewsService $newsService,
    ) {
        $this->middleware('permission:news.view', ['only' => ['index', 'show', 'search']]);
        $this->middleware('permission:news.create', ['only' => ['create', 'store']]);
        $this->middleware('permission:news.edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:news.delete', ['only' => ['destroy']]);
        $this->middleware(['throttle:news-write', 'quota:news_write'], ['only' => ['store', 'update']]);
    }

    public function index(Request $request): View
    {
        $news = News::query()
            ->with(['categories', 'category', 'user'])
            ->when($request->filled('search'), static function (Builder $q) use ($request): void {
                $searchTerm = (string) $request->input('search');
                $q->where(static function (Builder $sub) use ($searchTerm): void {
                    $sub->where('title', 'like', "%{$searchTerm}%")
                        ->orWhere('description', 'like', "%{$searchTerm}%")
                        ->orWhereHas('category', static function (Builder $catQ) use ($searchTerm): void {
                            $catQ->where('name', 'like', "%{$searchTerm}%");
                        });
                });
            })
            ->orderByDesc('created_at')
            ->paginate(10)
            ->withQueryString();

        return view('admin.news.index', compact('news'));
    }

    public function search(Request $request): JsonResponse
    {
        $search = trim((string) $request->get('search', ''));
        if ($search === '') {
            return response()->json(['items' => []], 200);
        }

        $items = News::query()
            ->with('category')
            ->where(static function (Builder $q) use ($search): void {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhereHas('category', static function (Builder $catQ) use ($search): void {
                        $catQ->where('name', 'like', "%{$search}%");
                    });
            })
            ->orderByDesc('created_at')
            ->limit(5)
            ->get()
            ->map(static fn (News $news): array => [
                'id'       => $news->id,
                'text'     => $news->title,
                'category' => $news->category?->name,
            ]);

        return response()->json(['items' => $items], 200);
    }

    public function show(News $news): View
    {
        $news->load(['category', 'categories', 'user', 'image', 'tags', 'newsSource']);

        return view('admin.news.show', compact('news'));
    }

    public function create(): View
    {
        $categories = Category::orderBy('name')->get();

        return view('admin.news.create', compact('categories'));
    }

    public function store(StoreNewsRequest $request): RedirectResponse
    {
        $this->newsService->createNews($request->validated(), (int) auth()->id());

        return redirect()->route('admin.news.index')
            ->with('success', 'Haber başarıyla oluşturuldu.');
    }

    public function edit(News $news): View
    {
        $news->load(['tags', 'image']);
        $categories = Category::orderBy('name')->get();

        return view('admin.news.edit', compact('news', 'categories'));
    }

    public function update(UpdateNewsRequest $request, News $news): RedirectResponse
    {
        $this->newsService->updateNews($news, $request->validated());

        return redirect()->route('admin.news.index')
            ->with('success', 'Haber başarıyla güncellendi.');
    }

    public function destroy(News $news): RedirectResponse
    {
        $this->newsService->deleteNews($news);

        return redirect()->route('admin.news.index')
            ->with('success', 'Haber başarıyla silindi.');
    }
}
