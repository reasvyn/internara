<?php

declare(strict_types=1);

namespace App\Livewire\Concerns;

use Illuminate\Database\Eloquent\Builder;

trait WithSorting
{
    public array $sortBy = ['column' => 'id', 'direction' => 'asc'];

    /** @var string[] */
    protected array $sortableColumns = ['id', 'name', 'created_at', 'updated_at'];

    protected function applySorting(Builder $query): Builder
    {
        $column = $this->sortBy['column'] ?? 'id';
        if (! in_array($column, $this->sortableColumns, true)) {
            $column = 'id';
        }

        $direction = $this->sortBy['direction'] ?? 'asc';
        if (! in_array($direction, ['asc', 'desc'], true)) {
            $direction = 'asc';
        }

        return $query->orderBy($column, $direction);
    }
}
