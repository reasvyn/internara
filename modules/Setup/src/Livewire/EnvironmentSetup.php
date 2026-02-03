<?php

declare(strict_types=1);

namespace Modules\Setup\Livewire;

use Livewire\Attributes\Computed;
use Livewire\Component;
use Modules\Setup\Livewire\Concerns\HandlesSetupSteps;
use Modules\Setup\Services\Contracts\SetupService;
use Modules\Setup\Services\Contracts\SystemAuditor;

/**
 * Handles the environment validation step of the application setup wizard.
 * Displays system requirements, directory permissions, and database connectivity.
 */
class EnvironmentSetup extends Component
{
    use HandlesSetupSteps;

    /**
     * The system auditor service instance.
     */
    protected SystemAuditor $auditor;

    /**
     * Initializes the component and performs the initial environment check.
     */
    public function boot(SetupService $setupService, SystemAuditor $auditor): void
    {
        $this->setupService = $setupService;
        $this->auditor = $auditor;

        $this->initSetupStepProps(
            currentStep: 'environment',
            nextStep: 'school',
            prevStep: 'welcome',
        );

        $this->requireSetupAccess();
    }

    /**
     * Retrieves the current system audit results.
     *
     * @return array<string, array<string, bool|string>>
     */
    #[Computed]
    public function audit(): array
    {
        return $this->auditor->audit();
    }

    /**
     * Determines if the 'Next' button should be disabled based on audit results.
     */
    #[Computed]
    public function disableNextStep(): bool
    {
        return ! $this->auditor->passes();
    }

    /**
     * Renders the environment setup view.
     */
    public function render(): \Illuminate\Contracts\View\View
    {
        return view('setup::livewire.environment-setup')
            ->layout('auth::components.layouts.auth')
            ->title(__('setup::setup.environment_validation'));
    }
}
