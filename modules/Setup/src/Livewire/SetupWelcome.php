<?php

declare(strict_types=1);

namespace Modules\Setup\Livewire;

use Illuminate\View\View;
use Livewire\Component;
use Modules\Setup\Services\Contracts\SetupService;

/**
 * Represents the initial 'Welcome' screen of the application setup process.
 */
class SetupWelcome extends Component
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
        $this->initSetupStepProps(currentStep: 'welcome', nextStep: 'environment');

        notify(
            __('setup::wizard.welcome.toast_greeting', ['app' => setting('app_name', 'Internara')]),
            'info',
        );
    }

    /**
     * Renders the component's view.
     */
    public function render(): View
    {
        return view('setup::livewire.setup-welcome')->layout('setup::components.layouts.setup', [
            'title' => __('setup::wizard.welcome.title').
                ' | '.
                setting('site_title', setting('app_name')),
        ]);
    }
}
