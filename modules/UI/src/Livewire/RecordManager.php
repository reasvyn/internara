<?php

declare(strict_types=1);

namespace Modules\UI\Livewire;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;
use Modules\Exception\Concerns\HandlesAppException;
use Modules\Shared\Livewire\Concerns\ManagesRecords;

/**
 * Class RecordManager
 *
 * Base abstract component for unified record management across all modules.
 * Implements standard CRUD orchestration, search, sort, and bulk actions.
 */
abstract class RecordManager extends Component
{
    use HandlesAppException;
    use ManagesRecords;

    /**
     * The model class for Policy-based authorization.
     */
    protected string $modelClass = '';

    /**
     * Permissions for granular access control.
     */
    protected string $viewPermission = '';

    protected string $createPermission = '';

    protected string $updatePermission = '';

    protected string $deletePermission = '';

    /**
     * Contextual filters for specific record types.
     */
    #[Url]
    public array $filters = [];

    /**
     * The page title.
     */
    public string $title = '';

    /**
     * The page subtitle.
     */
    public string $subtitle = '';

    /**
     * The context label for navigation.
     */
    public string $context = '';

    /**
     * Label for the "Add" button.
     */
    public string $addLabel = '';

    /**
     * Label for the "Delete Confirmation" message.
     */
    public string $deleteConfirmMessage = '';

    /**
     * Initialize the component with basic metadata.
     */
    abstract public function initialize(): void;

    /**
     * Define the table headers.
     *
     * @return array<int, array<string, mixed>>
     */
    abstract protected function getTableHeaders(): array;

    /**
     * Retrieves the table headers.
     */
    #[\Livewire\Attributes\Computed]
    public function headers(): array
    {
        return $this->getTableHeaders();
    }

    /**
     * Transform a raw model record into a UI-ready structure.
     * Overriding this allows children to format dates, currency, etc.
     */
    protected function mapRecord(mixed $record): array
    {
        // Default behavior: return model as array
        return $record->toArray();
    }

    /**
     * Retrieves the paginated and formatted collection of records.
     */
    #[\Livewire\Attributes\Computed]
    public function records(): \Illuminate\Pagination\LengthAwarePaginator
    {
        $appliedFilters = array_filter(array_merge($this->filters, [
            'search' => $this->search,
            'sort_by' => $this->sortBy['column'] ?? 'created_at',
            'sort_dir' => $this->sortBy['direction'] ?? 'desc',
        ]), fn ($value) => $value !== null && $value !== '' && $value !== []);

        $paginator = $this->service->paginate($appliedFilters, $this->perPage);

        // Apply UI Transformation
        return $paginator->through(fn ($item) => (object) $this->mapRecord($item));
    }

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $this->initialize();

        // Authorize page access
        if ($this->viewPermission) {
            $this->authorize($this->viewPermission);
        }

        if (empty($this->addLabel)) {
            $this->addLabel = __('ui::common.add');
        }

        if (empty($this->deleteConfirmMessage)) {
            $this->deleteConfirmMessage = __('ui::common.delete_confirm');
        }
    }

    /**
     * Handle updated filters by resetting pagination.
     */
    public function updatedFilters(): void
    {
        $this->resetPage();
        $this->selectedIds = [];
    }

    /**
     * Determine if the user can perform a specific action.
     */
    public function can(string $action, mixed $target = null): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }

        // Determine if we are checking at the class level or instance level
        $isClassLevel = $target === null;
        $target = $target ?: ($this->modelClass ?: null);

        return match ($action) {
            'view' => $this->viewPermission ? $user->can($this->viewPermission) : true,
            'create' => $this->createPermission ? $user->can($this->createPermission) : true,
            'update' => $this->updatePermission ? $user->can($this->updatePermission) : ($target && ! $isClassLevel ? $user->can('update', $target) : true),
            'delete' => $this->deletePermission ? $user->can($this->deletePermission) : ($target && ! $isClassLevel ? $user->can('delete', $target) : true),
            default => false,
        };
    }

    /**
     * {@inheritdoc}
     */
    public function edit(mixed $id): void
    {
        $record = $this->service->find($id);

        if ($record) {
            $this->authorize('update', $record);

            if (property_exists($this, 'form')) {
                // Ensure form supports setUser (common in our modules)
                if (method_exists($this->form, 'setUser')) {
                    $this->form->setUser($record);
                } else {
                    $this->form->fill($record);
                }
                $this->toggleModal(self::MODAL_FORM, true, ['id' => $id]);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function save(): void
    {
        if (! property_exists($this, 'form')) {
            return;
        }

        $this->form->validate();

        try {
            if ($this->form->id) {
                $record = $this->service->find($this->form->id);
                if ($record && $this->updatePermission) {
                    $this->authorize($this->updatePermission, $record);
                }
                $this->service->update($this->form->id, $this->form->all());
            } else {
                if ($this->createPermission) {
                    $roles = property_exists($this->form, 'roles') ? $this->form->roles : null;
                    $this->authorize($this->createPermission, [$this->modelClass ?: \Modules\User\Models\User::class, $roles]);
                }
                $this->service->create($this->form->all());
            }

            $this->toggleModal(self::MODAL_FORM, false);
            flash()->success(__('shared::messages.record_saved'));
        } catch (\Throwable $e) {
            $this->handleAppExceptionInLivewire($e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function remove(mixed $id = null): void
    {
        $id = $id ?: $this->recordId;
        $record = $this->service->find($id);

        if ($record) {
            $this->authorize('delete', $record);

            if ($this->service->delete($id)) {
                $this->toggleModal(self::MODAL_CONFIRM, false);
                $this->recordId = null;
                flash()->success(__('shared::messages.record_deleted'));
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function removeSelected(): void
    {
        if (empty($this->selectedIds)) {
            return;
        }

        try {
            foreach ($this->selectedIds as $id) {
                $record = $this->service->find($id);
                if ($record) {
                    $this->authorize('delete', $record);
                }
            }

            $count = $this->service->destroy($this->selectedIds);
            $this->selectedIds = [];
            flash()->success($this->getBulkDeleteMessage($count));
        } catch (\Throwable $e) {
            flash()->error($e->getMessage());
        }
    }
}
