<?php

declare(strict_types=1);

namespace App\Domain\Core\Livewire\Concerns;

use Illuminate\Database\Eloquent\Builder;

trait WithSorting
{
    public array $sortBy = ['column' => 'id', 'direction' => 'asc'];

    /** @var string[] */
    protected array $sortableColumns = ['id', 'name', 'created_at', 'updated_at'];

    protected function applySorting(Builder $query): Builder
    {
        $column = in_array($this->sortBy['column'], $this->sortableColumns, true)
            ? $this->sortBy['column']
            : 'id';

        $direction = in_array($this->sortBy['direction'], ['asc', 'desc'], true)
            ? $this->sortBy['direction']
            : 'asc';

        return $query->orderBy($column, $direction);
    }
}
