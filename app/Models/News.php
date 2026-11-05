<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\NewsStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class News extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'news';

    protected $fillable = [
        'title',
        'description',
        'content',
        'category_id',
        'image_id',
        'user_id',
        'news_source_id',
        'source_url',
        'source_author',
        'source_published_at',
        'reading_time',
        'is_active',
        'is_breaking',
        'is_featured',
        'is_editor_pick',
        'status',
        'views',
        'slug',
        'meta_title',
        'meta_description',
        'content_hash',
    ];

    protected $casts = [
        'is_active'           => 'boolean',
        'is_breaking'         => 'boolean',
        'is_featured'         => 'boolean',
        'is_editor_pick'      => 'boolean',
        'status'              => NewsStatus::class,
        'views'               => 'integer',
        'reading_time'        => 'integer',
        'image_id'            => 'integer',
        'category_id'         => 'integer',
        'user_id'             => 'integer',
        'news_source_id'      => 'integer',
        'source_published_at' => 'datetime',
        'created_at'          => 'datetime',
        'updated_at'          => 'datetime',
        'deleted_at'          => 'datetime',
    ];

    // ─── İLİŞKİLER ────────────────────────────────────

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    /**
     * @return BelongsToMany<Category, $this>
     */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'category_news')
            ->withTimestamps();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return BelongsTo<Image, $this>
     */
    public function image(): BelongsTo
    {
        return $this->belongsTo(Image::class, 'image_id');
    }

    /**
     * @return HasMany<Comment, $this>
     */
    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    /**
     * @return BelongsToMany<Tag, $this>
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'news_tag')
            ->withTimestamps();
    }

    /**
     * @return BelongsTo<NewsSource, $this>
     */
    public function newsSource(): BelongsTo
    {
        return $this->belongsTo(NewsSource::class, 'news_source_id');
    }

    // ─── SCOPE'LAR ─────────────────────────────────────

    /**
     * @param Builder<self> $query
     * @return Builder<self>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * @param Builder<self> $query
     * @return Builder<self>
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', NewsStatus::Published)->where('is_active', true);
    }

    /**
     * @param Builder<self> $query
     * @return Builder<self>
     */
    public function scopeBreaking(Builder $query): Builder
    {
        return $this->scopePublished($query->where('is_breaking', true));
    }

    /**
     * @param Builder<self> $query
     * @return Builder<self>
     */
    public function scopeFeatured(Builder $query): Builder
    {
        return $this->scopePublished($query->where('is_featured', true));
    }

    /**
     * @param Builder<self> $query
     * @return Builder<self>
     */
    public function scopeEditorPicks(Builder $query): Builder
    {
        return $this->scopePublished($query->where('is_editor_pick', true));
    }

    /**
     * @param Builder<self> $query
     * @return Builder<self>
     */
    public function scopeMostRead(Builder $query, int $limit = 10): Builder
    {
        return $this->scopePublished($query)->orderByDesc('views')->limit($limit);
    }

    /**
     * @param Builder<self> $query
     * @return Builder<self>
     */
    public function scopeMostCommented(Builder $query, int $limit = 10): Builder
    {
        return $this->scopePublished($query)
            ->withCount('comments')
            ->orderByDesc('comments_count')
            ->limit($limit);
    }

    // ─── YARDIMCI METODLAR ─────────────────────────────

    /**
     * Benzersiz haber slug'ı üret
     */
    public static function generateUniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $slug = Str::slug($title);
        if ($slug === '') {
            $slug = 'haber-' . uniqid();
        }

        $originalSlug = Str::limit($slug, 175, '');
        $slug = $originalSlug;
        $counter = 2;

        while (
            static::withTrashed()
                ->where('slug', $slug)
                ->when($ignoreId !== null, static fn (Builder $q): Builder => $q->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = $originalSlug . '-' . $counter++;
        }

        return $slug;
    }

    /**
     * İçerikten tahmini okuma süresini hesapla (dakika, Türkçe Unicode uyumlu)
     */
    public static function calculateReadingTime(string $content): int
    {
        $plain = strip_tags($content);
        $wordCount = preg_match_all('/[\p{L}\p{N}]+/u', $plain);

        return max(1, (int) ceil(((int) $wordCount) / 200));
    }

    /**
     * İçerik hash'i oluştur (duplikasyon kontrolü)
     */
    public static function generateContentHash(string $title, string $content): string
    {
        $normalized = mb_strtolower(
            trim($title) . '|' . trim(Str::limit(strip_tags($content), 500, '')),
            'UTF-8'
        );

        return hash('sha256', $normalized);
    }

    /**
     * Benzer haberleri bul (N+1 önlemek için category & image eager-loaded)
     *
     * @return Collection<int, self>
     */
    public function relatedNews(int $limit = 5): Collection
    {
        $tagIds = $this->relationLoaded('tags')
            ? $this->tags->pluck('id')->all()
            : $this->tags()->pluck('tags.id')->all();

        return self::query()
            ->with(['category', 'image'])
            ->published()
            ->where('id', '!=', $this->id)
            ->where(function (Builder $query) use ($tagIds): void {
                $query->where('category_id', $this->category_id);
                if ($tagIds !== []) {
                    $query->orWhereHas('tags', static function (Builder $q) use ($tagIds): void {
                        $q->whereIn('tags.id', $tagIds);
                    });
                }
            })
            ->latest()
            ->limit($limit)
            ->get();
    }

    /**
     * Yayın tarihini insan dostu formatta döndür
     */
    public function getPublishedDateAttribute(): string
    {
        $date = $this->source_published_at ?? $this->created_at;
        if ($date === null) {
            return '';
        }

        $diff = (int) $date->diffInMinutes(now());

        return match (true) {
            $diff < 1     => 'Az önce',
            $diff < 60    => $diff . ' dakika önce',
            $diff < 1440  => (int) $date->diffInHours(now()) . ' saat önce',
            $diff < 10080 => (int) $date->diffInDays(now()) . ' gün önce',
            default       => $date->format('d.m.Y H:i'),
        };
    }

    /**
     * Görsel URL'sini döndür (yedek görsel dahil)
     */
    public function getImageUrlAttribute(): string
    {
        if ($this->image && $this->image->path) {
            return asset('storage/' . $this->image->path);
        }

        return asset('images/placeholder.svg');
    }

    // ─── BOOT ──────────────────────────────────────────

    protected static function booted(): void
    {
        static::saving(static function (self $news): void {
            if ($news->isDirty('content') || !$news->reading_time) {
                $news->reading_time = static::calculateReadingTime($news->content ?? '');
            }

            if ($news->isDirty(['title', 'content']) || !$news->content_hash) {
                $news->content_hash = static::generateContentHash(
                    $news->title ?? '',
                    $news->content ?? ''
                );
            }
        });
    }
}
