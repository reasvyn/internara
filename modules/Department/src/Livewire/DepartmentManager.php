<?php

declare(strict_types=1);

namespace Modules\Department\Livewire;

use Illuminate\View\View;
use Livewire\Component;
use Modules\Department\Livewire\Forms\DepartmentForm;
use Modules\Shared\Livewire\Concerns\ManagesRecords;

class DepartmentManager extends Component
{
    use ManagesRecords;

    public DepartmentForm $form;

    public function boot(
        \Modules\Department\Services\Contracts\DepartmentService $departmentService,
    ): void {
        $this->service = $departmentService;
        $this->eventPrefix = 'department';
    }

    public function mount(): void
    {
        $isSetupPhase =
            session(\Modules\Setup\Services\Contracts\SetupService::SESSION_SETUP_AUTHORIZED) ===
                true || is_testing();

        if ($isSetupPhase) {
            return;
        }

        $this->authorize('department.view');
    }

    public function render(): View
    {
        return view('department::livewire.department-manager', [
            'records' => $this->records,
        ])->layout('ui::components.layouts.dashboard', [
            'title' => __('department::ui.manage_departments') . ' | ' . setting('brand_name', setting('app_name')),
        ]);
    }
}
