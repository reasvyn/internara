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
    public function boot(SetupService $setupService, SystemAuditor $auditor): void
    {
        $this->setupService = $setupService;
        $this->auditor = $auditor;

        $this->initSetupStepProps(
            currentStep: SetupService::STEP_ENVIRONMENT,
            nextStep: SetupService::STEP_SCHOOL,
            prevStep: SetupService::STEP_WELCOME,
        );

        $this->requireSetupAccess();
    }

    /**
     * Retrieves the current system audit results.
     */
    #[Computed]
    public function audit(): array
    {
        return $this->auditor->audit();
    }

    /**
     * Determines if the 'Next' button should be disabled.
     */
    #[Computed]
    public function disableNextStep(): bool
    {
        return ! $this->auditor->passes();
    }

    /**
     * Re-runs the system audit manually.
     */
    public function refreshAudit(): void
    {
        // Clearing the computed properties will force a re-audit on the next render
        unset($this->audit);
        unset($this->disableNextStep);

        flash()->success(__('setup::wizard.environment.audit_refreshed'));
    }

    /**
     * Renders the component view.
     */
    public function render(): \Illuminate\Contracts\View\View
    {
        return view('setup::livewire.environment-setup')->layout(
            'setup::components.layouts.setup',
            [
                'title' => __('setup::wizard.environment.title').
                    ' | '.
                    setting('site_title', setting('app_name')),
            ],
        );
    }
}
