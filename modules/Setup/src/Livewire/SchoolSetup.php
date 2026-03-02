<?php

declare(strict_types=1);

namespace Modules\Setup\Livewire;

use Illuminate\View\View;
use Livewire\Attributes\On;
use Livewire\Component;
use Modules\Setup\Services\Contracts\SetupService;

/**
 * Represents the 'School Identity' setup step in the application setup process.
 */
class SchoolSetup extends Component
{
    use Concerns\HandlesSetupSteps;

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
            currentStep: SetupService::STEP_SCHOOL,
            nextStep: SetupService::STEP_ACCOUNT,
            prevStep: SetupService::STEP_ENVIRONMENT,
            extra: ['req_record' => 'school'],
        );

        $this->requireSetupAccess();
    }

    /**
     * Handles the 'school_saved' event to proceed to the next step.
     */
    #[On('school_saved')]
    public function handleSchoolSaved(): void
    {
        $this->validate([
            'turnstile' => [new \Modules\Shared\Rules\Turnstile],
            'contact_me' => [new \Modules\Shared\Rules\Honeypot],
        ]);

        $this->nextStep();
    }

    /**
     * Renders the component view.
     */
    public function render(): View
    {
        return view('setup::livewire.school-setup')->layout('setup::components.layouts.setup', [
            'title' => __('setup::wizard.school.title').
                ' | '.
                setting('site_title', setting('app_name')),
        ]);
    }
}
