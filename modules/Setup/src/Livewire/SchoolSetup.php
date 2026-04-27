<?php

declare(strict_types=1);

namespace Modules\Setup\Livewire;

use Illuminate\View\View;
use Livewire\Attributes\On;
use Livewire\Component;
use Modules\Exception\AppException;
use Modules\Setup\Services\Contracts\AppSetupService;
use Modules\Shared\Livewire\Concerns\HandlesWizardSteps;
use Modules\Shared\Rules\Honeypot;
use Modules\Shared\Rules\Turnstile;

/**
 * Represents the 'School Identity' setup step in the application setup process.
 */
class SchoolSetup extends Component
{
    use HandlesWizardSteps;

    /**
     * Turnstile token for S1 security compliance.
     */
    public ?string $turnstile = null;

    /**
     * Honeypot field for bot protection.
     */
    public ?string $contact_me = null;

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
            currentStep: AppAppSetupService::STEP_SCHOOL,
            nextStep: AppAppSetupService::STEP_ACCOUNT,
            prevStep: '',
            extra: ['req_record' => AppAppSetupService::RECORD_SCHOOL],
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
            $this->validate([
                'turnstile' => [new Turnstile()],
                'contact_me' => [new Honeypot()],
            ]);

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
