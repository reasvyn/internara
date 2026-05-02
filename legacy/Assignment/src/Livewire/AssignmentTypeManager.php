<?php

declare(strict_types=1);

namespace Modules\Assignment\Livewire;

use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Modules\Assignment\Livewire\Forms\AssignmentTypeForm;
use Modules\Permission\Enums\Permission;
use Modules\UI\Livewire\RecordManager;

/**
 * Class AssignmentTypeManager
 *
 * Allows administrators to manage dynamic assignment categories/types.
 */
class AssignmentTypeManager extends RecordManager
{
    public AssignmentTypeForm $form;

    /**
     * Initialize the component metadata and services.
     */
    public function boot(AssignmentTypeService $assignmentTypeService): void
    {
        $this->service = $assignmentTypeService;
        $this->eventPrefix = 'assignment-type';
        $this->modelClass = AssignmentType::class;
    }

    /**
     * Configure the component's basic properties.
     */
    public function initialize(): void
    {
        $this->title = __('assignment::ui.menu.assignment_types');
        $this->subtitle = __('assignment::ui.type_subtitle');
        $this->context = 'assignment::ui.menu.assignments';
        $this->addLabel = __('assignment::ui.add_type');
        $this->deleteConfirmMessage = __('assignment::ui.delete_type_confirm');

        $this->viewPermission = Permission::JOURNAL_VIEW;
        $this->createPermission = Permission::JOURNAL_MANAGE;
        $this->updatePermission = Permission::JOURNAL_MANAGE;
        $this->deletePermission = Permission::JOURNAL_MANAGE;

        $this->searchable = ['name', 'slug', 'description'];
        $this->sortable = ['name', 'slug', 'group', 'created_at'];
    }

    /**
     * Define the table structure.
     */
    protected function getTableHeaders(): array
    {
        return [
            ['key' => 'name', 'label' => __('assignment::ui.name'), 'sortable' => true],
            ['key' => 'slug', 'label' => __('assignment::ui.slug'), 'sortable' => true],
            ['key' => 'group_label', 'label' => __('assignment::ui.group'), 'sort_by' => 'group'],
            ['key' => 'actions', 'label' => __('ui::common.actions'), 'class' => 'w-1'],
        ];
    }

    /**
     * Transform raw record for UI display.
     */
    protected function mapRecord(mixed $record): array
    {
        return array_merge($record->toArray(), [
            'group_label' => ucfirst($record->group),
        ]);
    }

    /**
     * Get available groups for the form.
     */
    #[Computed]
    public function groups(): array
    {
        return [
            ['id' => 'report', 'name' => __('Report')],
            ['id' => 'presentation', 'name' => __('Presentation')],
            ['id' => 'certification', 'name' => __('Certification')],
            ['id' => 'other', 'name' => __('Other')],
        ];
    }

    /**
     * Render the component view.
     */
    public function render(): View
    {
        return view('assignment::livewire.assignment-type-manager');
    }
}
