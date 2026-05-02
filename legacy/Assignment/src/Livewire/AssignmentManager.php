<?php

declare(strict_types=1);

namespace Modules\Assignment\Livewire;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Modules\Assignment\Livewire\Forms\AssignmentForm;
use Modules\Assignment\Services\Contracts\AssignmentService;
use Modules\Permission\Enums\Permission;
use Modules\UI\Livewire\RecordManager;

/**
 * Class AssignmentManager
 *
 * Allows administrators to manage internship assignments and policies.
 */
class AssignmentManager extends RecordManager
{
    public AssignmentForm $form;

    /**
     * Initialize the component metadata and services.
     */
    public function boot(AssignmentService $assignmentService): void
    {
        $this->service = $assignmentService;
        $this->eventPrefix = 'assignment';
        $this->modelClass = Assignment::class;
    }

    /**
     * Configure the component's basic properties.
     */
    public function initialize(): void
    {
        $this->title = __('assignment::ui.manage_assignments');
        $this->subtitle = __('assignment::ui.subtitle');
        $this->context = 'assignment::ui.menu.assignments';
        $this->addLabel = __('assignment::ui.add_assignment');
        $this->deleteConfirmMessage = __('assignment::ui.delete_confirm');

        $this->viewPermission = Permission::JOURNAL_VIEW;
        $this->createPermission = Permission::JOURNAL_MANAGE;
        $this->updatePermission = Permission::JOURNAL_MANAGE;
        $this->deletePermission = Permission::JOURNAL_MANAGE;

        $this->searchable = ['title', 'description'];
        $this->sortable = ['title', 'is_mandatory', 'due_date', 'created_at'];
    }

    /**
     * Define the table structure.
     */
    protected function getTableHeaders(): array
    {
        return [
            ['key' => 'title', 'label' => __('assignment::ui.title'), 'sortable' => true],
            ['key' => 'type_name', 'label' => __('assignment::ui.type')],
            [
                'key' => 'is_mandatory',
                'label' => __('assignment::ui.is_mandatory'),
                'sortable' => true,
            ],
            ['key' => 'due_date', 'label' => __('assignment::ui.due_date'), 'sortable' => true],
            ['key' => 'actions', 'label' => __('ui::common.actions'), 'class' => 'w-1'],
        ];
    }

    /**
     * Transform raw record for UI display.
     */
    protected function mapRecord(mixed $record): array
    {
        return array_merge($record->toArray(), [
            'type_name' => $record->type?->name ?? '-',
        ]);
    }

    /**
     * Fetch and transform records for the table.
     */
    #[Computed]
    public function records(): LengthAwarePaginator
    {
        return $this->service
            ->query($this->filters)
            ->with(['type'])
            ->paginate($this->perPage)
            ->through(fn ($assignment) => $this->mapRecord($assignment));
    }

    /**
     * Get available assignment types for the form.
     */
    #[Computed]
    public function types(): array
    {
        return $this->service->getTypes();
    }

    /**
     * Render the component view.
     */
    public function render(): View
    {
        return view('assignment::livewire.assignment-manager');
    }
}
