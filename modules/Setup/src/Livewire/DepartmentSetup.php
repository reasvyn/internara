<?php

declare(strict_types=1);

namespace Modules\Setup\Livewire;

use Illuminate\View\View;
use Livewire\Attributes\On;
use Livewire\Component;
use Modules\Setup\Services\Contracts\AppSetupService;
use Modules\Shared\Livewire\Concerns\HandlesWizardSteps;

/**
 * Represents the 'Department Setup' step in the application setup process.
 */
class DepartmentSetup extends Component
{
    use HandlesWizardSteps;

    /**
     * Initializes the component.
     */
    public function boot(AppSetupService $setupService): void
    {
        $this->setupService = $setupService;
    }

    /**
     * Mounts the component.
     */
    public function mount(): void
    {
        $this->initWizardStepProps(
            currentStep: AppSetupService::STEP_DEPARTMENT,
            nextStep: AppSetupService::STEP_INTERNSHIP,
            prevStep: AppSetupService::STEP_ACCOUNT,
            extra: ['req_record' => AppSetupService::RECORD_DEPARTMENT],
        );

        $this->requireWizardAccess();
    }

    /**
     * Re-evaluates step status after records are modified.
     */
    #[On('department:saved')]
    #[On('department:deleted')]
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
        return view('setup::livewire.department-setup')->layout('setup::components.layouts.setup', [
            'title' =>
                __('setup::wizard.department.title') .
                ' | ' .
                setting('site_title', setting('app_name')),
        ]);
    }
}
