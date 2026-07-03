<?php

declare(strict_types=1);

namespace App\Core\Livewire\Concerns;

use Illuminate\Database\Eloquent\Builder;

trait WithSorting
{
    public array $sortBy = ['column' => 'created_at', 'direction' => 'desc'];

    /** @var string[] */
    protected array $sortableColumns = ['id', 'name', 'created_at', 'updated_at'];

    protected function applySorting(Builder $query): Builder
    {
        $defaultColumn = $this->sortBy['column'] ?? 'created_at';
        $column = in_array($defaultColumn, $this->sortableColumns, true)
            ? $defaultColumn
            : 'created_at';

        $defaultDirection = $this->sortBy['direction'] ?? 'desc';
        $direction = in_array($defaultDirection, ['asc', 'desc'], true)
            ? $defaultDirection
            : 'desc';

        return $query->orderBy($column, $direction);
    }
}
