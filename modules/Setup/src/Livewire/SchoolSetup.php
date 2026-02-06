<?php

declare(strict_types=1);

namespace Modules\Setup\Livewire;

use Illuminate\View\View;
use Livewire\Attributes\On;
use Livewire\Component;
use Modules\Setup\Services\Contracts\SetupService;

/**
 * Represents the 'School Setup' step in the application setup process.
 * This component is responsible for setting up initial school data.
 */
class SchoolSetup extends Component
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
            currentStep: 'school',
            nextStep: 'account',
            prevStep: 'environment',
            extra: ['req_record' => 'school'],
        );

        $this->requireSetupAccess();
    }

    /**
     * Handles the 'school_saved' event to proceed to the next setup step.
     */
    #[On('school_saved')]
    public function handleSchoolSaved(): void
    {
        $this->nextStep();
    }

    /**
     * Renders the component's view.
     *
     * @return \Illuminate\View\View The view for the school setup step.
     */
    public function render(): View
    {
        return view('setup::livewire.school-setup')->layout('setup::components.layouts.setup', [
            'title' => __('setup::wizard.school.title').' | '.setting('site_title', 'Internara'),
        ]);
    }
}
