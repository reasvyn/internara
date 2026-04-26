<?php

declare(strict_types=1);

namespace Modules\Setup\Livewire;

use Illuminate\View\View;
use Livewire\Component;
use Modules\Setup\Services\Contracts\SetupService;

/**
 * Represents the 'Internship Setup' step in the application setup process.
 */
class InternshipSetup extends Component
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
            currentStep: SetupService::STEP_INTERNSHIP,
            nextStep: SetupService::STEP_SYSTEM,
            prevStep: SetupService::STEP_DEPARTMENT,
            extra: ['req_record' => SetupService::RECORD_INTERNSHIP],
        );

        $this->requireSetupAccess();
    }

    /**
     * Re-evaluates step status after records are modified.
     */
    #[\Livewire\Attributes\On('internship:saved')]
    #[\Livewire\Attributes\On('internship:deleted')]
    public function handleRecordsChanged(): void
    {
        unset($this->isRecordExists);
        unset($this->disableNextStep);
    }

    /**
     * Renders the component view.
     */
    public function render(): View
    {
        return view('setup::livewire.internship-setup')->layout('setup::components.layouts.setup', [
            'title' => __('setup::wizard.internship.title').
                ' | '.
                setting('site_title', setting('app_name')),
        ]);
    }
}
