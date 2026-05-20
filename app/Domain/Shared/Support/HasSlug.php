<?php

declare(strict_types=1);

namespace App\Domain\Shared\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

/**
 * Provides slug generation and query scoping for Eloquent models.
 *
 * Usage:
 *   class Internship extends BaseModel
 *   {
 *       use HasSlug;
 *   }
 *
 * Requires a `slug` string column on the model's table.
 */
trait HasSlug
{
    public static function bootHasSlug(): void
    {
        static::creating(function ($model) {
            if (empty($model->slug)) {
                $model->slug = Str::slug($model->{static::slugSource()});
            }
        });
    }

    protected static function slugSource(): string
    {
        return 'name';
    }

    public function scopeWhereSlug(Builder $query, string $slug): Builder
    {
        return $query->where('slug', $slug);
    }
}
