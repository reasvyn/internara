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
 */
class EnvironmentSetup extends Component
{
    use HandlesSetupSteps;

    /**
     * The system auditor service instance.
     */
    protected SystemAuditor $auditor;

    /**
     * Initializes the component.
     */
    public function boot(SetupService $setupService): void
    {
        $this->setupService = $setupService;

        $this->initSetupStepProps(
            currentStep: 'environment',
            nextStep: 'school',
            prevStep: 'welcome',
        );

        $this->requireSetupAccess();
    }

    /**
     * Retrieves the current system audit results.
     */
    #[Computed]
    public function audit(): array
    {
        return app(SystemAuditor::class)->audit();
    }

    /**
     * Determines if the 'Next' button should be disabled.
     */
    #[Computed]
    public function disableNextStep(): bool
    {
        return !app(SystemAuditor::class)->passes();
    }

    /**
     * Renders the component view.
     */
    public function render(): \Illuminate\Contracts\View\View
    {
        return view('setup::livewire.environment-setup')
            ->layout('setup::components.layouts.setup')
            ->title(__('setup::setup.environment_validation'));
    }
}
