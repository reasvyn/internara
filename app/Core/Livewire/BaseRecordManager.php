<?php

declare(strict_types=1);

namespace App\Core\Livewire;

use App\Livewire\Concerns\WithRecordSelection;
use App\Livewire\Concerns\WithSorting;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

abstract class BaseRecordManager extends Component
{
    use WithPagination, WithRecordSelection, WithSorting;

    public string $search = '';

    public int $perPage = 10;

    public array $filters = [];

    /** @var string[] */
    protected array $with = [];

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedFilters(): void
    {
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->filters = [];
        $this->resetPage();
    }

    abstract public function headers(): array;

    abstract protected function query(): Builder;

    public function rows(): LengthAwarePaginator
    {
        if (! in_array($this->perPage, $this->perPageOptions(), true)) {
            $this->perPage = 10;
        }

        $query = $this->query();

        if ($this->with !== []) {
            $query = $query->with($this->with);
        }

        if ($this->search) {
            $query = $this->applySearch($query);
        }

        $query = $this->applyFilters($query);

        $query = $this->applySorting($query);

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

    protected function applyFilters(Builder $query): Builder
    {
        return $query;
    }

    protected function performBulkAction(string $name, callable $callback, bool $transactional = true): void
    {
        if (empty($this->selectedIds)) {
            flash()->warning(__('common.actions.no_records_selected'));

            return;
        }

        $work = function () use ($callback): void {
            foreach ($this->selectedIds as $id) {
                $callback($id);
            }
        };

        if ($transactional) {
            DB::transaction($work);
        } else {
            $work();
        }

        flash()->success(
            __('common.actions.bulk_action_done', ['count' => count($this->selectedIds), 'action' => $name]),
        );
        $this->clearSelection();
    }

    protected function performMassAction(string $name, callable $callback): void
    {
        $query = $this->query();

        if ($this->with !== []) {
            $query = $query->with($this->with);
        }

        if ($this->search) {
            $query = $this->applySearch($query);
        }

        $query = $this->applyFilters($query);

        $count = $query->count();

        if ($count === 0) {
            flash()->warning(__('common.actions.no_records_matching'));

            return;
        }

        $callback($query);

        flash()->success(
            __('common.actions.mass_action_done', ['count' => $count, 'action' => $name]),
        );
        $this->clearSelection();
    }
}
