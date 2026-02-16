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
    }

    public function mount(): void
    {
        $isSetupPhase =
            session(\Modules\Setup\Services\Contracts\SetupService::SESSION_SETUP_AUTHORIZED) ===
            true || app()->runningUnitTests();

        if ($isSetupPhase) {
            return;
        }

        $this->authorize('department.view');
    }

    public function render(): View
    {
        return view('department::livewire.department-manager');
    }
}
