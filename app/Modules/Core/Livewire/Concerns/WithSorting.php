<?php

declare(strict_types=1);

namespace App\Modules\Core\Livewire\Concerns;

use Illuminate\Database\Eloquent\Builder;

trait WithSorting
{
    public array $sortBy = ['column' => 'created_at', 'direction' => 'desc'];

    /** @var string[] Baseline whitelist; managers widen it from their headers(). */
    protected array $sortableColumns = ['id', 'name', 'created_at', 'updated_at'];

    protected function applySorting(Builder $query): Builder
    {
        $allowed = $this->resolveSortableColumns($query);

        $requested = $this->sortBy['column'] ?? null;
        $column = is_string($requested) && in_array($requested, $allowed, true)
            ? $requested
            : ($allowed[0] ?? $query->getModel()->getKeyName());

        $direction = ($this->sortBy['direction'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        return $query->orderBy($column, $direction);
    }

    /**
     * Columns the table may be ordered by. Always a whitelist — never raw input.
     *
     * @return string[]
     */
    protected function resolveSortableColumns(Builder $query): array
    {
        return $this->sortableColumns;
    }
}
