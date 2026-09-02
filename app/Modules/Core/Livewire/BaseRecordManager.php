<?php

declare(strict_types=1);

namespace App\Modules\Core\Livewire;

use App\Modules\Core\Livewire\Concerns\WithRecordSelection;
use App\Modules\Core\Livewire\Concerns\WithSorting;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Component;
use Livewire\WithPagination;
use TallStackUi\Traits\Interactions;

abstract class BaseRecordManager extends Component
{
    use Interactions;
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

        return $this->buildQuery()->paginate($this->perPage);
    }

    protected function buildQuery(): Builder
    {
        $query = $this->query();

        if ($this->with !== []) {
            $query = $query->with($this->with);
        }

        if ($this->search) {
            $query = $this->applySearch($query);
        }

        return $this->applySorting($this->applyFilters($query));
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

    protected function performBulkAction(
        string $name,
        callable $callback,
    ): void {
        if (empty($this->selectedIds)) {
            $this->toast()->warning(__('common.actions.no_records_selected'))->send();

            return;
        }

        foreach ($this->selectedIds as $id) {
            $callback($id);
        }

        $this->toast()->success(
            __('common.actions.bulk_action_done', [
                'count' => count($this->selectedIds),
                'action' => $name,
            ]),
        )->send();
        $this->clearSelection();
    }

    protected function performMassAction(string $name, callable $callback): void
    {
        $query = $this->buildQuery();
        $count = $query->count();

        if ($count === 0) {
            $this->toast()->warning(__('common.actions.no_records_matching'))->send();

            return;
        }

        $callback($query);

        $this->toast()->success(
            __('common.actions.mass_action_done', ['count' => $count, 'action' => $name]),
        )->send();
        $this->clearSelection();
    }
}
