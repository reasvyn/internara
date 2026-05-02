<?php

declare(strict_types=1);

namespace App\Livewire\Concerns;

/**
 * Trait for handling dynamic sorting in Eloquent queries.
 */
trait WithSorting
{
    /**
     * Sorting state compatible with MaryUI.
     * format: ['column' => 'name', 'direction' => 'asc']
     */
    public array $sortBy = ['column' => 'created_at', 'direction' => 'desc'];

    /**
     * Handle sorting update.
     */
    public function updatedSortBy(): void
    {
        // Handled by Livewire lifecycle
    }

    /**
     * Apply sorting to the query.
     */
    protected function applySorting($query)
    {
        return $query->orderBy($this->sortBy['column'], $this->sortBy['direction']);
    }
}
