<?php

declare(strict_types=1);

namespace Modules\Setup\Livewire;

use Illuminate\View\View;
use Livewire\Component;
use Modules\Setup\Services\Contracts\SetupService;

/**
 * Represents the 'Department Setup' step in the application setup process.
 */
class DepartmentSetup extends Component
{
    use Concerns\HandlesSetupSteps;

    /**
     * Initializes the component.
     */
    public function boot(SetupService $setupService): void
    {
        $this->setupService = $setupService;
    }

    /**
     * Mounts the component.
     */
    public function mount(): void
    {
        $this->initSetupStepProps(
            currentStep: SetupService::STEP_DEPARTMENT,
            nextStep: SetupService::STEP_INTERNSHIP,
            prevStep: SetupService::STEP_ACCOUNT,
            extra: ['req_record' => 'department'],
        );

        $this->requireSetupAccess();
    }

    /**
     * Renders the component view.
     */
    public function render(): View
    {
        return view('setup::livewire.department-setup')->layout('setup::components.layouts.setup', [
            'title' => __('setup::wizard.department.title').
                ' | '.
                setting('site_title', setting('app_name')),
        ]);
    }
}
