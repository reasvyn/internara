<?php

declare(strict_types=1);

namespace Modules\Setup\Livewire;

use Illuminate\View\View;
use Livewire\Attributes\On;
use Livewire\Component;
use Modules\Exception\AppException;
use Modules\Setup\Services\Contracts\AppSetupService;
use Modules\Shared\Livewire\Concerns\HandlesWizardSteps;

/**
 * Represents the 'School Identity' setup step in the application setup process.
 */
class SchoolSetup extends Component
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
            currentStep: AppSetupService::STEP_SCHOOL,
            nextStep: AppSetupService::STEP_ACCOUNT,
            prevStep: '',
            extra: ['req_record' => AppSetupService::RECORD_SCHOOL],
        );

        $this->requireWizardAccess();
    }

    /**
     * Handles the 'school_saved' event to proceed to the next step.
     */
    #[On('school_saved')]
    public function handleSchoolSaved(): void
    {
        try {
            $this->validate([]);

            $this->nextStep();
        } catch (\Exception $e) {
            report($e);
            flash()->error(
                $e instanceof AppException
                    ? $e->getUserMessage()
                    : __('ui::errors.unexpected_technical_failure'),
            );
        }
    }

    /**
     * Renders the component view.
     */
    public function render(): View
    {
        return view('setup::livewire.school-setup')->layout('setup::components.layouts.setup', [
            'title' =>
                __('setup::wizard.school.title') .
                ' | ' .
                setting('site_title', setting('app_name')),
        ]);
    }
}
