<?php

declare(strict_types=1);

namespace Modules\Setup\Livewire;

use Illuminate\View\View;
use Livewire\Component;
use Modules\Setup\Services\Contracts\SetupService;

/**
 * Represents the final 'Complete' screen of the application setup process.
 */
class SetupComplete extends Component
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
            currentStep: SetupService::STEP_COMPLETE,
            nextStep: '',
            prevStep: SetupService::STEP_SYSTEM,
            extra: ['landing_route' => 'login'],
        );

        $this->requireSetupAccess();
    }

    /**
     * Renders the component's view.
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
