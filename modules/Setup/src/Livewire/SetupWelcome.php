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
        if ($this->setupService->isAppInstalled()) {
             $this->redirectRoute('login', navigate: true);
             return;
        }

        $this->initSetupStepProps(
            currentStep: SetupService::STEP_WELCOME,
            nextStep: SetupService::STEP_ENVIRONMENT,
        );

        // [S1 - Secure] Atomic Initialization Audit
        $lock = \Illuminate\Support\Facades\Cache::lock('setup.init', 10);
        
        $lock->get(function () {
            if (! session()->has('setup_audit_logged')) {
                activity('setup')
                    ->event('started')
                    ->log('Administrator reached the setup initialization screen.');
                
                session()->put('setup_audit_logged', true);
            }
        });
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
