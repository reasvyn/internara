<?php

declare(strict_types=1);

namespace App\Core\Livewire;

use App\Core\Livewire\Concerns\WithSorting;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Base class for read-only record list components.
 *
 * Unlike BaseRecordManager (sysadmin full CRUD with selection and bulk actions),
 * BaseRecordList is for user-facing views where records are displayed for
 * reading only — no mutations, no selection, no bulk actions.
 *
 * Provides:
 * - Pagination
 * - Optional search
 * - Optional sorting
 * - Per-page configuration
 *
 * Examples: student logbook list, supervisor review list, certificate list
 */
abstract class BaseRecordList extends Component
{
    use WithPagination;

    public string $search = '';

    public int $perPage = 10;

    /** @var string[] */
    protected array $with = [];

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    abstract protected function query(): Builder;

    public function rows(): LengthAwarePaginator
    {
        if (!in_array($this->perPage, $this->perPageOptions(), true)) {
            $this->perPage = 10;
        }

        $query = $this->query();

        if ($this->with !== []) {
            $query = $query->with($this->with);
        }

        if ($this->search) {
            $query = $this->applySearch($query);
        }

        return $query->paginate($this->perPage);
    }

    protected function perPageOptions(): array
    {
        return [10, 25, 50, 100];
    }

    protected function applySearch(Builder $query): Builder
    {
        return $query;
    }
}
