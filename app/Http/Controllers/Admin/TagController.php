<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveTagRequest;
use App\Models\Tag;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class TagController extends Controller
{
    public function index(): View
    {
        $tags = Tag::orderByDesc('usage_count')->paginate(15);

        return view('admin.tags.index', compact('tags'));
    }

    public function create(): View
    {
        return view('admin.tags.create');
    }

    public function store(SaveTagRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $validated['slug'] = $this->generateUniqueSlug((string) $validated['name']);

        Tag::create($validated);
        Cache::forget('tags.trending');

        return redirect()->route('admin.tags.index')
            ->with('success', 'Etiket başarıyla eklendi.');
    }

    public function edit(Tag $tag): View
    {
        return view('admin.tags.edit', compact('tag'));
    }

    public function update(SaveTagRequest $request, Tag $tag): RedirectResponse
    {
        $validated = $request->validated();
        if ($validated['name'] !== $tag->name) {
            $validated['slug'] = $this->generateUniqueSlug((string) $validated['name'], (int) $tag->id);
        }

        $tag->update($validated);
        Cache::forget('tags.trending');

        return redirect()->route('admin.tags.index')
            ->with('success', 'Etiket başarıyla güncellendi.');
    }

    public function destroy(Tag $tag): RedirectResponse
    {
        $tag->news()->detach();
        $tag->delete();
        Cache::forget('tags.trending');

        return redirect()->route('admin.tags.index')
            ->with('success', 'Etiket başarıyla silindi.');
    }

    private function generateUniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name);
        $original = $base !== '' ? $base : 'etiket';
        $slug = $original;
        $counter = 1;

        while (
            Tag::query()
                ->where('slug', $slug)
                ->when($ignoreId !== null, static fn ($q) => $q->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = $original . '-' . $counter++;
        }

        return $slug;
    }
}
