<?php

declare(strict_types=1);

namespace App\Core\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;

trait HasCommonScopes
{
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeInactive(Builder $query): Builder
    {
        return $query->where('is_active', false);
    }

    public function scopeRecent(Builder $query, int $limit = 50): Builder
    {
        return $query->latest()->limit($limit);
    }

    public function scopeCreatedAfter(Builder $query, string $date): Builder
    {
        return $query->where('created_at', '>=', $date);
    }

    public function scopeCreatedBefore(Builder $query, string $date): Builder
    {
        return $query->where('created_at', '<=', $date);
    }

    public function scopeOrdered(Builder $query, string $column = 'created_at', string $direction = 'desc'): Builder
    {
        return $query->orderBy($column, $direction);
    }
}
