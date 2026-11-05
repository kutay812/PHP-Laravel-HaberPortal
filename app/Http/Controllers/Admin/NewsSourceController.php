<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveNewsSourceRequest;
use App\Jobs\FetchNewsJob;
use App\Models\Category;
use App\Models\NewsSource;
use App\Services\QueueTriggerService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;

class NewsSourceController extends Controller
{
    public function __construct()
    {
        $this->middleware('role:Super Admin|Admin');
    }

    public function index(): View
    {
        $sources = NewsSource::with('defaultCategory')->orderBy('name')->paginate(10);

        return view('admin.sources.index', compact('sources'));
    }

    public function create(): View
    {
        $categories = Category::orderBy('name')->get();

        return view('admin.sources.create', compact('categories'));
    }

    public function store(SaveNewsSourceRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $validated['slug'] = $this->generateUniqueSlug((string) $validated['name']);

        NewsSource::create($validated);

        return redirect()->route('admin.sources.index')
            ->with('success', 'Haber kaynağı başarıyla eklendi.');
    }

    public function edit(NewsSource $source): View
    {
        $categories = Category::orderBy('name')->get();

        return view('admin.sources.edit', compact('source', 'categories'));
    }

    public function update(SaveNewsSourceRequest $request, NewsSource $source): RedirectResponse
    {
        $validated = $request->validated();
        if ($validated['name'] !== $source->name) {
            $validated['slug'] = $this->generateUniqueSlug((string) $validated['name'], (int) $source->id);
        }

        $source->update($validated);

        return redirect()->route('admin.sources.index')
            ->with('success', 'Haber kaynağı başarıyla güncellendi.');
    }

    public function destroy(NewsSource $source): RedirectResponse
    {
        $source->delete();

        return redirect()->route('admin.sources.index')
            ->with('success', 'Haber kaynağı başarıyla silindi.');
    }

    /**
     * Belirli kaynak için asenkron çekme görevini kuyruğa ekle ve arka plan işçisini tetikle.
     */
    public function fetch(NewsSource $source, QueueTriggerService $queueTrigger): RedirectResponse
    {
        FetchNewsJob::dispatch((int) $source->id, true);
        $queueTrigger->triggerWorkerIfNeeded();

        return redirect()->back()->with(
            'success',
            "{$source->name} kaynağı için haber çekme ve özgünleştirme işlemi arka planda başlatıldı."
        );
    }

    private function generateUniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name);
        $original = $base !== '' ? $base : 'kaynak';
        $slug = $original;
        $counter = 1;

        while (
            NewsSource::query()
                ->where('slug', $slug)
                ->when($ignoreId !== null, static fn ($q) => $q->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = $original . '-' . $counter++;
        }

        return $slug;
    }
}
