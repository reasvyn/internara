<?php

declare(strict_types=1);

namespace Modules\Setup\Livewire;

use Illuminate\View\View;
use Livewire\Component;
use Modules\Setup\Services\Contracts\SetupService;

/**
 * Represents the 'Internship Data' step in the application setup process.
 * This component is responsible for setting up initial internship-related data.
 */
class InternshipSetup extends Component
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
     * Mounts the component, initializes setup properties, and ensures step progression is valid.
     */
    public function mount(): void
    {
        $this->initSetupStepProps(
            currentStep: 'internship',
            nextStep: 'system',
            prevStep: 'department',
            extra: ['req_record' => 'internship'],
        );

        $this->requireSetupAccess();
    }

    /**
     * Renders the component's view.
     *
     * @return \Illuminate\View\View The view for the internship setup step.
     */
    public function render(): View
    {
        return view('setup::livewire.internship-setup')->layout('setup::components.layouts.setup', [
            'title' => __('setup::wizard.internship.title').' | '.setting('site_title', 'Internara'),
        ]);
    }
}
