<?php

declare(strict_types=1);

namespace Modules\Department\Livewire;

use Illuminate\View\View;
use Modules\Department\Livewire\Forms\DepartmentForm;
use Modules\Department\Models\Department;
use Modules\Department\Services\Contracts\DepartmentService;
use Modules\UI\Livewire\RecordManager;

/**
 * Class DepartmentManager
 *
 * Handles the administrative interface for managing academic departments (Jurusan).
 */
class DepartmentManager extends RecordManager
{
    public DepartmentForm $form;

    /**
     * Initialize the component metadata and services.
     */
    public function boot(DepartmentService $departmentService): void
    {
        $this->service = $departmentService;
        $this->eventPrefix = 'department';
        $this->modelClass = Department::class;
    }

    /**
     * Configure the component's basic properties.
     */
    public function initialize(): void
    {
        $this->title = __('department::ui.title');
        $this->subtitle = __('department::ui.subtitle');
        $this->context = 'admin::ui.menu.departments';
        $this->addLabel = __('department::ui.add');
        $this->deleteConfirmMessage = __('department::ui.delete_confirm');

        $isSetupPhase =
            session(\Modules\Setup\Services\Contracts\SetupService::SESSION_SETUP_AUTHORIZED) ===
                true || is_testing();

        if (! $isSetupPhase) {
            $this->viewPermission = 'department.view';
            $this->createPermission = 'department.create';
            $this->updatePermission = 'department.update';
            $this->deletePermission = 'department.delete';
        }
    }

    /**
     * Define the table structure.
     */
    protected function getTableHeaders(): array
    {
        return [
            ['key' => 'name', 'label' => __('department::ui.name'), 'sortable' => true],
            ['key' => 'description', 'label' => __('ui::common.description')],
            [
                'key' => 'created_at_formatted',
                'label' => __('ui::common.created_at'),
                'sort_by' => 'created_at',
            ],
            ['key' => 'actions', 'label' => '', 'class' => 'w-1'],
        ];
    }

    /**
     * Transform raw department record for UI display.
     */
    protected function mapRecord(mixed $record): array
    {
        return array_merge($record->toArray(), [
            'created_at_formatted' => $record->created_at->translatedFormat('d M Y H:i'),
        ]);
    }

    /**
     * Render the component view.
     */
    public function render(): View
    {
        return view('department::livewire.department-manager')->layout(
            'ui::components.layouts.dashboard',
            [
                'title' => $this->title.' | '.setting('brand_name', setting('app_name')),
                'context' => $this->context,
            ],
        );
    }
}
