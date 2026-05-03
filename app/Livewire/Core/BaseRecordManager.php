declare(strict_types=1);

namespace App\Livewire\Core;

use App\Livewire\Concerns\WithRecordSelection;
use App\Livewire\Concerns\WithSorting;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;

/**
 * Base class for all managerial "Record Manager" components.
 *
 * S2 - Sustain: Centralized logic for search, filter, sort, and actions.
 * S3 - Scalable: Handles Bulk (selected) vs Mass (query-based) operations.
 */
abstract class BaseRecordManager extends Component
{
    use Toast, WithPagination, WithRecordSelection, WithSorting;

    /**
     * Search term.
     */
    public string $search = '';

    /**
     * Filter parameters.
     */
    public array $filters = [];

    /**
     * Reset pagination when search or filters change.
     */
    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedFilters(): void
    {
        $this->resetPage();
    }

    /**
     * Define table headers.
     */
    abstract public function headers(): array;

    /**
     * Base query for the records.
     */
    abstract protected function query(): Builder;

    /**
     * Get the paginated rows.
     */
    public function rows(): LengthAwarePaginator
    {
        $query = $this->query();

        // Apply Search
        if ($this->search) {
            $query = $this->applySearch($query);
        }

        // Apply Filters
        $query = $this->applyFilters($query);

        // Apply Sorting
        $query = $this->applySorting($query);

        return $query->paginate($this->perPage());
    }

    /**
     * Number of items per page.
     */
    protected function perPage(): int
    {
        return 10;
    }

    /**
     * Logic for applying search to the query.
     */
    protected function applySearch(Builder $query): Builder
    {
        return $query;
    }

    /**
     * Logic for applying filters to the query.
     */
    protected function applyFilters(Builder $query): Builder
    {
        return $query;
    }

    /**
     * Perform a Bulk Action (Acts on $selectedIds).
     *
     * @param callable $callback Logic to execute for each selected ID.
     */
    protected function performBulkAction(string $name, callable $callback): void
    {
        if (empty($this->selectedIds)) {
            $this->warning(__('No records selected.'));

            return;
        }

        foreach ($this->selectedIds as $id) {
            $callback($id);
        }

        $this->success(
            __(':count records updated via :action.', [
                'count' => count($this->selectedIds),
                'action' => $name,
            ]),
        );
        $this->clearSelection();
    }

    /**
     * Perform a Mass Action (Acts on the active filtered query).
     * This is "tricky" because it targets EVERYTHING matching current filters.
     *
     * @param string $name Name of the action for feedback.
     * @param callable $callback Logic to execute on the query builder.
     */
    protected function performMassAction(string $name, callable $callback): void
    {
        $query = $this->query();

        if ($this->search) {
            $query = $this->applySearch($query);
        }

        $query = $this->applyFilters($query);

        $count = $query->count();

        if ($count === 0) {
            $this->warning(__('No records matching current filters.'));

            return;
        }

        $callback($query);

        $this->success(
            __(':count records processed via mass action: :action.', [
                'count' => $count,
                'action' => $name,
            ]),
        );
        $this->clearSelection();
    }
}
