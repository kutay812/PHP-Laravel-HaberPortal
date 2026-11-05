<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\NewsStatus;
use App\Models\Category;
use App\Models\Image;
use App\Models\News;
use App\Models\Tag;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class NewsService
{
    public function __construct(
        private readonly HtmlSanitizerService $sanitizer,
    ) {}

    /**
     * Create news article with associated tags and image.
     *
     * @param array<string, mixed> $data
     */
    public function createNews(array $data, ?int $authorId): News
    {
        $news = DB::transaction(function () use ($data, $authorId): News {
            $title = (string) $data['title'];
            $slug = News::generateUniqueSlug($title);

            $imageId = null;
            if (isset($data['image']) && $data['image'] instanceof UploadedFile && $data['image']->isValid()) {
                $imageId = $this->uploadImage($data['image']);
            } elseif (isset($data['new_image']) && $data['new_image'] instanceof UploadedFile && $data['new_image']->isValid()) {
                $imageId = $this->uploadImage($data['new_image']);
            }

            $sanitizedContent = $this->sanitizer->sanitize((string) ($data['content'] ?? ''));
            $isActive = isset($data['is_active']) ? (bool) $data['is_active'] : true;
            $status = $data['status'] ?? ($isActive ? NewsStatus::Published->value : NewsStatus::Draft->value);

            $news = News::create([
                'title'          => $title,
                'description'    => strip_tags((string) ($data['description'] ?? '')),
                'content'        => $sanitizedContent,
                'category_id'    => (int) $data['category_id'],
                'user_id'        => $authorId,
                'is_active'      => $isActive,
                'is_featured'    => isset($data['is_featured']) ? (bool) $data['is_featured'] : false,
                'is_breaking'    => isset($data['is_breaking']) ? (bool) $data['is_breaking'] : false,
                'is_editor_pick' => isset($data['is_editor_pick']) ? (bool) $data['is_editor_pick'] : false,
                'status'         => $status,
                'slug'           => $slug,
                'image_id'       => $imageId,
                'views'          => 0,
            ]);

            if (!empty($data['tags']) && is_string($data['tags'])) {
                $this->syncTags($news, $data['tags']);
            }

            return $news;
        });

        $this->flushPublicCaches((int) $news->category_id);

        return $news;
    }

    /**
     * Update news article safely with HTML sanitization and accurate tag counter delta.
     *
     * @param array<string, mixed> $data
     */
    public function updateNews(News $news, array $data): News
    {
        $oldCategoryId = (int) $news->category_id;

        $updated = DB::transaction(function () use ($news, $data): News {
            if (isset($data['title']) && is_string($data['title']) && $news->title !== $data['title']) {
                $news->slug = News::generateUniqueSlug($data['title'], $news->id);
            }

            $uploadedImage = $data['image'] ?? $data['new_image'] ?? null;
            if ($uploadedImage instanceof UploadedFile && $uploadedImage->isValid()) {
                if ($news->image) {
                    Storage::disk('public')->delete($news->image->path);
                    $news->image->delete();
                }

                $news->image_id = $this->uploadImage($uploadedImage);
            }

            $content = isset($data['content'])
                ? $this->sanitizer->sanitize((string) $data['content'])
                : $news->content;

            $description = isset($data['description'])
                ? strip_tags((string) $data['description'])
                : $news->description;

            $isActive = array_key_exists('is_active', $data)
                ? (bool) $data['is_active']
                : (bool) $news->is_active;

            $status = $data['status'] ?? (
                array_key_exists('is_active', $data)
                    ? ($isActive ? NewsStatus::Published->value : NewsStatus::Draft->value)
                    : $news->status
            );

            $news->fill([
                'title'          => $data['title'] ?? $news->title,
                'description'    => $description,
                'content'        => $content,
                'category_id'    => isset($data['category_id']) ? (int) $data['category_id'] : $news->category_id,
                'is_active'      => $isActive,
                'is_featured'    => array_key_exists('is_featured', $data) ? (bool) $data['is_featured'] : $news->is_featured,
                'is_breaking'    => array_key_exists('is_breaking', $data) ? (bool) $data['is_breaking'] : $news->is_breaking,
                'is_editor_pick' => array_key_exists('is_editor_pick', $data) ? (bool) $data['is_editor_pick'] : $news->is_editor_pick,
                'status'         => $status,
            ]);

            $news->save();

            if (array_key_exists('tags', $data)) {
                $this->syncTags($news, (string) ($data['tags'] ?? ''));
            }

            return $news;
        });

        $this->flushPublicCaches($oldCategoryId, (int) $updated->category_id);

        return $updated;
    }

    /**
     * Delete news article and its image safely.
     */
    public function deleteNews(News $news): void
    {
        $categoryId = (int) $news->category_id;

        DB::transaction(static function () use ($news): void {
            if ($news->image) {
                Storage::disk('public')->delete($news->image->path);
                $news->image->delete();
            }

            $detachedTagIds = $news->tags()->pluck('tags.id')->all();
            if ($detachedTagIds !== []) {
                Tag::whereIn('id', $detachedTagIds)
                    ->where('usage_count', '>', 0)
                    ->decrement('usage_count');
                $news->tags()->detach();
            }

            $news->delete();
        });

        $this->flushPublicCaches($categoryId);
    }

    private function flushPublicCaches(?int ...$categoryIds): void
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

        $ids = array_values(array_unique(array_filter($categoryIds)));
        if ($ids !== []) {
            $slugs = Category::whereIn('id', $ids)->pluck('slug')->all();
            foreach ($slugs as $slug) {
                if (is_string($slug) && $slug !== '') {
                    Cache::forget('news.category_block.' . $slug);
                    Cache::forget('news.featured.' . $slug);
                }
            }
        }
    }

    private function uploadImage(UploadedFile $imageFile): int
    {
        $path = (string) $imageFile->store('news-images', 'public');
        $image = Image::create([
            'path' => $path,
            'name' => $imageFile->getClientOriginalName(),
        ]);

        return (int) $image->id;
    }

    private function syncTags(News $news, string $tagsString): void
    {
        $tagNames = array_map('trim', explode(',', $tagsString));
        $tagIds = [];

        foreach ($tagNames as $tagName) {
            $slug = Str::slug($tagName);
            if ($tagName !== '' && $slug !== '') {
                $tag = Tag::firstOrCreate(
                    ['slug' => $slug],
                    ['name' => $tagName]
                );
                $tagIds[] = (int) $tag->id;
            }
        }

        $tagIds = array_values(array_unique($tagIds));
        $changes = $news->tags()->sync($tagIds);

        if (!empty($changes['attached'])) {
            Tag::whereIn('id', $changes['attached'])->increment('usage_count');
        }

        if (!empty($changes['detached'])) {
            Tag::whereIn('id', $changes['detached'])
                ->where('usage_count', '>', 0)
                ->decrement('usage_count');
        }
    }
}
