<?php

declare(strict_types=1);

namespace Modules\Shared\Livewire\Concerns;

use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Modules\Shared\Services\Contracts\EloquentQuery;

/**
 * @mixin \Livewire\Component
 */
trait ManagesRecords
{
    /**
     * Default sorting field.
     */
    protected const DEFAULT_SORT_BY = 'created_at';

    /**
     * Default sorting direction.
     */
    protected const DEFAULT_SORT_DIR = 'desc';

    /**
     * Standard modal identifiers.
     */
    protected const MODAL_FORM = 'form-modal';

    protected const MODAL_CONFIRM = 'confirm-modal';

    /**
     * Standard event identifiers.
     */
    protected const EVENT_NOTIFY = 'notify';

    protected const EVENT_OPEN_MODAL = 'open-modal';

    protected const EVENT_CLOSE_MODAL = 'close-modal';

    protected const EVENT_CONFIRM_MODAL = 'show-confirm-modal';

    protected const EVENT_DESTROY_RECORD = 'destroy-record';

    protected EloquentQuery $service;

    public string $eventPrefix = '';

    #[Url(except: '')]
    public string $search = '';

    #[Url(except: self::DEFAULT_SORT_BY)]
    public string $sortBy = self::DEFAULT_SORT_BY;

    #[Url(except: self::DEFAULT_SORT_DIR)]
    public string $sortDir = self::DEFAULT_SORT_DIR;

    #[Url(except: EloquentQuery::DEFAULT_PER_PAGE)]
    public int $perPage = EloquentQuery::DEFAULT_PER_PAGE;

    public bool $formModal = false;

    public bool $confirmModal = false;

    public ?string $recordId = null;

    /**
     * Get the unique prefix for events.
     */
    protected function getEventPrefix(): string
    {
        return $this->eventPrefix ?: strtolower(class_basename($this));
    }

    /**
     * Define dynamic event listeners.
     */
    protected function getListeners(): array
    {
        return [
            $this->getEventPrefix().':destroy-record' => 'remove',
        ];
    }

    /**
     * Initialize properties for the trait.
     */
    public function initializeManagesRecords(): void
    {
        $this->search = '';
        $this->perPage = 15;
        $this->sortBy = 'created_at';
        $this->sortDir = 'desc';
        $this->formModal = false;
        $this->confirmModal = false;
    }

    /**
     * Get the paginated list of records.
     */
    #[Computed(persist: true)]
    public function records(): LengthAwarePaginator
    {
        $filters = [
            'search' => $this->search,
            'sort_by' => $this->sortBy,
            'sort_dir' => $this->sortDir,
        ];

        return $this->service->paginate(
            array_filter($filters), // Remove empty filters
            $this->perPage,
        );
    }

    /**
     * Open the form modal for adding a new record.
     */
    public function add(): void
    {
        $this->form->reset();
        $this->formModal = true;
        $this->dispatch($this->getEventPrefix().':open-modal', 'form-modal');
    }

    /**
     * Open the form modal for editing a record.
     */
    public function edit(mixed $id): void
    {
        $record = $this->service->find($id);

        if ($record) {
            $this->form->fill($record);
            $this->formModal = true;
            $this->dispatch($this->getEventPrefix().':open-modal', 'form-modal', ['id' => $id]);
        }
    }

    /**
     * Show a confirmation modal for removing a record.
     */
    public function discard(mixed $id): void
    {
        $this->recordId = (string) $id;
        $this->confirmModal = true;
        $this->dispatch($this->getEventPrefix().':show-confirm-modal', 'confirm-modal', [
            'id' => $id,
        ]);
    }

    /**
     * Save or update a record. Assumes the component has a 'form' property.
     */
    public function save(): void
    {
        $this->form->validate();

        $this->service->save(['id' => $this->form->id], $this->form->except('id'));

        $this->formModal = false;
        $this->dispatch($this->getEventPrefix().':close-modal', 'form-modal');
        notify(__('shared::messages.record_saved'), 'success');
    }

    /**
     * Listener for the delete confirmation event.
     */
    public function remove(mixed $id = null): void
    {
        $id = $id ?: $this->recordId;

        if ($id) {
            $this->service->delete($id);
            $this->confirmModal = false;
            $this->recordId = null;
            notify(__('shared::messages.record_deleted'), 'success');
        }
    }
}