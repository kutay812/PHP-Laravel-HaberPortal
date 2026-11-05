<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class NewsSource extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'url',
        'type',
        'logo_url',
        'website_url',
        'reliability_score',
        'default_category_id',
        'is_active',
        'auto_publish',
        'fetch_interval_minutes',
        'last_fetched_at',
        'total_fetched',
        'failed_fetches',
        'category_mappings',
        'notes',
    ];

    protected $casts = [
        'is_active'              => 'boolean',
        'auto_publish'           => 'boolean',
        'reliability_score'      => 'integer',
        'fetch_interval_minutes' => 'integer',
        'total_fetched'          => 'integer',
        'failed_fetches'         => 'integer',
        'last_fetched_at'        => 'datetime',
        'category_mappings'      => 'array',
    ];

    /**
     * @return HasMany<News, $this>
     */
    public function news(): HasMany
    {
        return $this->hasMany(News::class);
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function defaultCategory(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'default_category_id');
    }

    /**
     * @param Builder<self> $query
     * @return Builder<self>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function shouldFetch(): bool
    {
        if (!$this->is_active) {
            return false;
        }
        if (!$this->last_fetched_at) {
            return true;
        }

        return $this->last_fetched_at->addMinutes($this->fetch_interval_minutes)->isPast();
    }

    public function getReliabilityLevelAttribute(): string
    {
        return match (true) {
            $this->reliability_score >= 90 => 'very_high',
            $this->reliability_score >= 70 => 'high',
            $this->reliability_score >= 50 => 'medium',
            $this->reliability_score >= 30 => 'low',
            default                        => 'very_low',
        };
    }

    protected static function booted(): void
    {
        static::creating(static function (self $source): void {
            if (empty($source->slug)) {
                $source->slug = Str::slug((string) $source->name);
            }
        });
    }
}
