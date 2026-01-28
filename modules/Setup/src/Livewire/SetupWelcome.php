<?php

declare(strict_types=1);

namespace Modules\Setup\Livewire;

use Illuminate\View\View;
use Livewire\Component;
use Modules\Setup\Services\Contracts\SetupService;

/**
 * Represents the initial 'Welcome' screen of the application setup process.
 * This is the first step in the installation wizard.
 */
class SetupWelcome extends Component
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
     * Mounts the component and initializes the properties for the first setup step.
     */
    public function mount(): void
    {
        $this->initSetupStepProps(currentStep: 'welcome', nextStep: 'environment');
    }

    /**
     * Renders the component's view.
     *
     * @return \Illuminate\View\View The view for the welcome step.
     */
    public function render(): View
    {
        return view('setup::livewire.setup-welcome')->layout('setup::components.layouts.setup', [
            'title' => __('setup::wizard.welcome.title').' | '.setting('site_title', 'Internara'),
        ]);
    }
}
