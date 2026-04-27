<?php

declare(strict_types=1);

namespace Modules\Setup\Livewire;

use Illuminate\View\View;
use Livewire\Component;
use Modules\Setup\Services\Contracts\AppSetupService;
use Modules\Shared\Livewire\Concerns\HandlesWizardSteps;

/**
 * [S2 - Sustain] Entry point for the Application Setup Wizard.
 * Provides the architectural welcome and context for the installation journey.
 */
class SetupWelcome extends Component
{
    use HandlesWizardSteps;

    /**
     * Boots the component.
     */
    public function boot(AppSetupService $setupService): void
    {
        $this->setupService = $setupService;
    }

    /**
     * Mounts the component and initializes properties.
     */
    public function mount(): void
    {
        $this->initWizardStepProps(
            currentStep: AppSetupService::STEP_WELCOME,
            nextStep: AppSetupService::STEP_ENVIRONMENT,
        );

        // Security gate: only uninstalled apps can access welcome
        if ($this->setupService->isAppInstalled()) {
            abort(404);
        }
    }

    /**
     * Proceeds to the Environment Setup step.
     */
    public function nextStep(): void
    {
        $this->nextStep();
    }

    /**
     * Renders the component view.
     */
    public function render(): View
    {
        return view('setup::livewire.setup-welcome')
            ->layout('setup::components.layouts.setup', [
                'title' => __('setup::wizard.welcome.title').
                    ' | '.
                    setting('site_title', setting('app_name')),
            ]);
    }
}
