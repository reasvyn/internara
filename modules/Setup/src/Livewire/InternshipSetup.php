<?php

declare(strict_types=1);

namespace Modules\Setup\Livewire;

use Illuminate\View\View;
use Livewire\Attributes\On;
use Livewire\Component;
use Modules\Setup\Services\Contracts\AppSetupService;
use Modules\Shared\Livewire\Concerns\HandlesWizardSteps;

/**
 * Represents the 'Internship Setup' step in the application setup process.
 */
class InternshipSetup extends Component
{
    use HandlesWizardSteps;

    /**
     * Initializes the component.
     */
    public function boot(AppAppSetupService $setupService): void
    {
        $this->setupService = $setupService;
    }

    /**
     * Mounts the component.
     */
    public function mount(): void
    {
        $this->initWizardStepProps(
            currentStep: AppAppSetupService::STEP_INTERNSHIP,
            nextStep: AppAppSetupService::STEP_SYSTEM,
            prevStep: AppAppSetupService::STEP_DEPARTMENT,
            extra: ['req_record' => AppAppSetupService::RECORD_INTERNSHIP],
        );

        $this->requireWizardAccess();
    }

    /**
     * Re-evaluates step status after records are modified.
     */
    #[On('internship:saved')]
    #[On('internship:deleted')]
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
            'title' =>
                __('setup::wizard.internship.title') .
                ' | ' .
                setting('site_title', setting('app_name')),
        ]);
    }
}
