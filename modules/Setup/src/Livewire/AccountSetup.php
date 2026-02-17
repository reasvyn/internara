<?php

declare(strict_types=1);

namespace Modules\Setup\Livewire;

use Illuminate\View\View;
use Livewire\Attributes\On;
use Livewire\Component;
use Modules\Setup\Services\Contracts\SetupService;

/**
 * Represents the 'Account Creation' step in the application setup process.
 * This component is responsible for handling the creation of the initial SuperAdmin account.
 */
class AccountSetup extends Component
{
    use Concerns\HandlesSetupSteps;

    /**
     * Boots the component and injects the SetupService.
     *
     * @param SetupService $setupService The service for handling setup logic.
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
            currentStep: 'account',
            nextStep: 'department',
            prevStep: 'school',
            extra: ['req_record' => 'super-admin'],
        );

        $this->requireSetupAccess();
    }

    /**
     * Handles the 'super_admin_registered' event to proceed to the next setup step.
     */
    #[On('super_admin_registered')]
    public function handleSuperAdminRegistered(): void
    {
        $this->nextStep();
    }

    /**
     * Renders the component's view.
     *
     * @return \Illuminate\View\View The view for the account setup step.
     */
    public function render(): View
    {
        return view('setup::livewire.account-setup')->layout('setup::components.layouts.setup', [
            'title' => __('setup::wizard.account.title').
                ' | '.
                setting('site_title', setting('app_name')),
        ]);
    }
}
