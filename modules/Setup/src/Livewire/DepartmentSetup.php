<?php

declare(strict_types=1);

namespace Modules\Setup\Livewire;

use Illuminate\View\View;
use Livewire\Component;
use Modules\Setup\Services\Contracts\SetupService;

/**
 * Represents the 'Department Setup' step in the application setup process.
 * This component is responsible for setting up initial department data.
 */
class DepartmentSetup extends Component
{
    use Concerns\HandlesSetupSteps;

    /**
     * Boots the component and injects the SetupService.
     *
     * @param \Modules\Setup\Services\Contracts\SetupService $setupService The service for handling setup logic.
     */
    public function boot(SetupService $setupService): void
    {
        $this->setupService = $setupService;
    }

    /**
     * Mounts the component, initializes setup properties, and ensures step progression is valid.
     */
    public function mount(): void
    {
        $this->initSetupStepProps(
            currentStep: 'department',
            nextStep: 'internship',
            prevStep: 'account',
            extra: ['req_record' => 'department'],
        );

        $this->requireSetupAccess();
    }

    /**
     * Renders the component's view.
     *
     * @return \Illuminate\View\View The view for the department setup step.
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
