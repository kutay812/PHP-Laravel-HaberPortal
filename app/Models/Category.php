<?php

declare(strict_types=1);

namespace App\Models;

use App\Providers\AppServiceProvider;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;

class Category extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'main_category_id',
        'description',
        'title',
        'image_id',
        'slug',
    ];

    public function mainCategory(): BelongsTo
    {
        return $this->belongsTo(MainCategory::class);
    }

    public function image(): BelongsTo
    {
        return $this->belongsTo(Image::class);
    }

    public function news(): HasMany
    {
        return $this->hasMany(News::class);
    }

    protected static function booted(): void
    {
        $flushCache = static function (): void {
            Cache::forget(AppServiceProvider::GLOBAL_CATEGORIES_CACHE_KEY);
        };

        static::saved($flushCache);
        static::deleted($flushCache);
        static::restored($flushCache);
    }
}
