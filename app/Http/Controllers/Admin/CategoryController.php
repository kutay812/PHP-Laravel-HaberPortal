<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCategoryRequest;
use App\Http\Requests\Admin\UpdateCategoryRequest;
use App\Models\Category;
use App\Models\Image;
use App\Models\MainCategory;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:categories.view', ['only' => ['index', 'search']]);
        $this->middleware('permission:categories.create', ['only' => ['create', 'store']]);
        $this->middleware('permission:categories.edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:categories.delete', ['only' => ['destroy']]);
    }

    public function index(Request $request): View
    {
        $categories = Category::query()
            ->withCount('news')
            ->when($request->filled('search'), static function (Builder $q) use ($request): void {
                $searchTerm = (string) $request->input('search');
                $q->where('name', 'like', "%{$searchTerm}%");
            })
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return view('admin.categories.index', compact('categories'));
    }

    public function create(): View
    {
        $mainCategories = MainCategory::orderBy('name')->get();
        $images = Image::latest()->limit(100)->get();

        return view('admin.categories.create', compact('mainCategories', 'images'));
    }

    public function store(StoreCategoryRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $slug = Str::slug((string) $validated['name']);
        $originalSlug = $slug !== '' ? $slug : 'kategori';
        $slug = $originalSlug;
        $counter = 1;
        while (Category::withTrashed()->where('slug', $slug)->exists()) {
            $slug = $originalSlug . '-' . $counter++;
        }

        $category = new Category($validated);
        $category->slug = $slug;
        $category->save();

        return redirect()->route('admin.categories.index')
            ->with('success', 'Kategori başarıyla oluşturuldu.');
    }

    public function edit(Category $category): View
    {
        $mainCategories = MainCategory::orderBy('name')->get();
        $images = Image::latest()->limit(100)->get();

        return view('admin.categories.edit', compact('category', 'mainCategories', 'images'));
    }

    public function update(UpdateCategoryRequest $request, Category $category): RedirectResponse
    {
        $validated = $request->validated();

        if ($validated['name'] !== $category->name) {
            $slug = Str::slug((string) $validated['name']);
            $originalSlug = $slug !== '' ? $slug : 'kategori';
            $slug = $originalSlug;
            $counter = 1;
            while (Category::withTrashed()->where('slug', $slug)->where('id', '!=', $category->id)->exists()) {
                $slug = $originalSlug . '-' . $counter++;
            }
            $validated['slug'] = $slug;
        }

        $category->update($validated);

        return redirect()->route('admin.categories.index')
            ->with('success', 'Kategori başarıyla güncellendi.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        if ($category->news()->exists()) {
            return back()->with('error', 'Bu kategoriye ait haberler olduğu için silinemez.');
        }

        $category->delete();

        return redirect()->route('admin.categories.index')
            ->with('success', 'Kategori başarıyla silindi.');
    }

    public function search(Request $request): JsonResponse
    {
        $search = trim((string) $request->get('search', ''));
        if ($search === '') {
            return response()->json(['items' => []], 200);
        }

        $items = Category::query()
            ->withCount('news')
            ->where('name', 'like', "%{$search}%")
            ->orderByDesc('created_at')
            ->limit(5)
            ->get()
            ->map(static fn (Category $category): array => [
                'id'       => $category->id,
                'text'     => $category->name,
                'category' => $category->news_count . ' haber',
            ]);

        return response()->json(['items' => $items], 200);
    }
}
