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
     * Turnstile token for S1 security compliance.
     */
    public ?string $turnstile = null;

    /**
     * Honeypot field for bot protection.
     */
    public ?string $contact_me = null;

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
            currentStep: SetupService::STEP_ACCOUNT,
            nextStep: SetupService::STEP_DEPARTMENT,
            prevStep: SetupService::STEP_SCHOOL,
            extra: ['req_record' => SetupService::RECORD_SUPER_ADMIN],
        );

        $this->requireSetupAccess();
    }

    /**
     * Handles the 'super_admin_registered' event to proceed to the next setup step.
     */
    #[On('super_admin_registered')]
    public function handleSuperAdminRegistered(): void
    {
        try {
            $this->validate([
                'turnstile' => [new \Modules\Shared\Rules\Turnstile],
                'contact_me' => [new \Modules\Shared\Rules\Honeypot],
            ]);

            $this->nextStep();
        } catch (\Exception $e) {
             report($e);
             flash()->error($e instanceof \Modules\Exception\AppException ? $e->getUserMessage() : __('ui::errors.unexpected_technical_failure'));
        }
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
