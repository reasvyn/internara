<?php

declare(strict_types=1);

namespace Modules\Setup\Livewire;

use Illuminate\View\View;
use Livewire\Component;
use Modules\Setup\Services\Contracts\SetupService;

/**
 * Represents the final 'Completion' step in the application setup process.
 * This component handles the finalization of the setup.
 */
class SetupComplete extends Component
{
    use Concerns\HandlesSetupSteps;

    /**
     * Boots the component and injects the SetupService.
     *
     * @param \Modules\Setup\Contracts\Services\SetupService $setupService The service for handling setup logic.
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
            currentStep: SetupService::STEP_COMPLETE,
            prevStep: SetupService::STEP_SYSTEM,
        );

        $this->requireSetupAccess();
    }

    /**
     * Renders the component's view.
     *
     * @return \Illuminate\View\View The view for the setup completion step.
     */
    public function render(): View
    {
        return view('setup::livewire.setup-complete')->layout('setup::components.layouts.setup', [
            'title' => __('setup::wizard.complete.title').
                ' | '.
                setting('site_title', setting('app_name')),
        ]);
    }
}
