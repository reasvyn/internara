<?php

declare(strict_types=1);

namespace App\Livewire\Concerns;

use Illuminate\Database\Eloquent\Builder;

/**
 * Trait WithSorting
 *
 * Provides standardized sorting logic for Livewire components.
 */
trait WithSorting
{
    /**
     * Sort configuration.
     * format: ['column' => 'name', 'direction' => 'asc']
     */
    public array $sortBy = ['column' => 'id', 'direction' => 'asc'];

    /**
     * Apply sorting to the query builder.
     */
    protected function applySorting(Builder $query): Builder
    {
        return $query->orderBy($this->sortBy['column'], $this->sortBy['direction']);
    }
}
