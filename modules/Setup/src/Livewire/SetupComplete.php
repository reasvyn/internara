<?php

declare(strict_types=1);

namespace Modules\Setup\Livewire;

use Illuminate\View\View;
use Livewire\Component;
use Modules\Setup\Services\Contracts\AppSetupService;
use Modules\Shared\Livewire\Concerns\HandlesWizardSteps;

/**
 * Represents the final 'Complete' screen of the application setup process.
 */
class SetupComplete extends Component
{
    use HandlesWizardSteps;

    /**
     * Flags for the final user check-up.
     */
    public bool $data_verified = false;

    public bool $security_aware = false;

    public bool $legal_agreed = false;

    /**
     * Modal visibility flags.
     */
    public bool $showPrivacy = false;

    public bool $showTerms = false;

    /**
     * Initializes the component.
     */
    public function boot(AppSetupService $setupService): void
    {
        $this->setupService = $setupService;
    }

    /**
     * Mounts the component.
     */
    public function mount(): void
    {
        $this->initWizardStepProps(
            currentStep: AppSetupService::STEP_COMPLETE,
            nextStep: '',
            prevStep: AppSetupService::STEP_SYSTEM,
            extra: ['landing_route' => 'login'],
        );

        $this->requireWizardAccess();
    }

    /**
     * Handles the login attempt after validating the governance checklist.
     */
    public function nextStep(): void
    {
        // [S1 - Secure] Server-side mandate enforcement
        $this->validate([
            'data_verified' => 'accepted',
            'security_aware' => 'accepted',
            'legal_agreed' => 'accepted',
        ]);

        $currentStep = $this->wizardStepProps['currentStep'] ?? '';

        $success = $this->setupService->performSetupStep($currentStep);

        if ($success) {
            $this->redirectToLanding();
        }
    }

    /**
     * Renders the component's view.
     */
    public function render(): View
    {
        return view('setup::livewire.setup-complete')->layout('setup::components.layouts.setup', [
            'title' =>
                __('setup::wizard.complete.title') .
                ' | ' .
                setting('site_title', setting('app_name')),
        ]);
    }
}
