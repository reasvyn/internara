<?php

declare(strict_types=1);

namespace Modules\Shared\Livewire\Concerns;

use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Modules\Shared\Services\Contracts\EloquentQuery;

/**
 * Trait ManagesRecords
 *
 * Provides a standardized workflow for managing Eloquent records within Livewire components.
 * This trait orchestrates pagination, sorting, searching, and basic CRUD interactions,
 * delegating business logic to the injected Service layer.
 */
trait ManagesRecords
{
    /**
     * The standard sorting configurations.
     */
    protected const DEFAULT_SORT_BY = 'created_at';

    protected const DEFAULT_SORT_DIR = 'desc';

    /**
     * Standard modal and event identifiers.
     */
    protected const MODAL_FORM = 'form-modal';

    protected const MODAL_CONFIRM = 'confirm-modal';

    /**
     * The service instance responsible for domain logic.
     */
    protected EloquentQuery $service;

    /**
     * Optional prefix for component-specific events.
     */
    public string $eventPrefix = '';

    /**
     * Search query for filtering records.
     */
    #[Url(except: '')]
    public string $search = '';

    /**
     * The field to sort the records by.
     */
    #[Url(except: self::DEFAULT_SORT_BY)]
    public string $sortBy = self::DEFAULT_SORT_BY;

    /**
     * The direction to sort the records.
     */
    #[Url(except: self::DEFAULT_SORT_DIR)]
    public string $sortDir = self::DEFAULT_SORT_DIR;

    /**
     * Number of records to display per page.
     */
    #[Url(except: EloquentQuery::DEFAULT_PER_PAGE)]
    public int $perPage = EloquentQuery::DEFAULT_PER_PAGE;

    /**
     * Visibility state of the form modal.
     */
    public bool $formModal = false;

    /**
     * Visibility state of the confirmation modal.
     */
    public bool $confirmModal = false;

    /**
     * The ID of the record currently being processed (e.g., for deletion).
     */
    public ?string $recordId = null;

    /**
     * Initializes the trait's default state.
     */
    public function initializeManagesRecords(): void
    {
        $this->search = '';
        $this->perPage = EloquentQuery::DEFAULT_PER_PAGE;
        $this->sortBy = self::DEFAULT_SORT_BY;
        $this->sortDir = self::DEFAULT_SORT_DIR;
    }

    /**
     * Retrieves the paginated collection of records.
     */
    #[Computed(persist: true)]
    public function records(): LengthAwarePaginator
    {
        $filters = array_filter([
            'search' => $this->search,
            'sort_by' => $this->sortBy,
            'sort_dir' => $this->sortDir,
        ]);

        return $this->service->paginate($filters, $this->perPage);
    }

    /**
     * Prepares the component for creating a new record.
     */
    public function add(): void
    {
        if (property_exists($this, 'form')) {
            $this->form->reset();
        }

        $this->toggleModal(self::MODAL_FORM, true);
    }

    /**
     * Prepares the component for editing an existing record.
     */
    public function edit(mixed $id): void
    {
        $record = $this->service->find($id);

        if ($record && property_exists($this, 'form')) {
            $this->form->fill($record);
            $this->toggleModal(self::MODAL_FORM, true, ['id' => $id]);
        }
    }

    /**
     * Triggers the confirmation flow for removing a record.
     */
    public function discard(mixed $id): void
    {
        $this->recordId = (string) $id;
        $this->toggleModal(self::MODAL_CONFIRM, true, ['id' => $id]);
    }

    /**
     * Persists the record changes to the database.
     */
    public function save(): void
    {
        if (property_exists($this, 'form')) {
            $this->form->validate();

            $this->service->save(['id' => $this->form->id], $this->form->except('id'));

            $this->toggleModal(self::MODAL_FORM, false);
            notify(__('shared::messages.record_saved'), 'success');
        }
    }

    /**
     * Finalizes the removal of a record.
     */
    public function remove(mixed $id = null): void
    {
        $id = $id ?: $this->recordId;

        if ($id && $this->service->delete($id)) {
            $this->toggleModal(self::MODAL_CONFIRM, false);
            $this->recordId = null;
            notify(__('shared::messages.record_deleted'), 'success');
        }
    }

    /**
     * Manages modal visibility and dispatches corresponding UI events.
     */
    protected function toggleModal(string $name, bool $visible, array $params = []): void
    {
        $property = $name === self::MODAL_FORM ? 'formModal' : 'confirmModal';
        $this->{$property} = $visible;

        $event = $visible ? 'open-modal' : 'close-modal';
        $this->dispatch($this->getEventPrefix().':'.$event, $name, $params);
    }

    /**
     * Resolves the event prefix for the component.
     */
    protected function getEventPrefix(): string
    {
        return $this->eventPrefix ?: strtolower(class_basename($this));
    }

    /**
     * Registers component listeners.
     */
    protected function getListeners(): array
    {
        return [
            $this->getEventPrefix().':destroy-record' => 'remove',
        ];
    }
}
