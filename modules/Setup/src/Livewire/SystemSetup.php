<?php

declare(strict_types=1);

namespace Modules\Setup\Livewire;

use Illuminate\View\View;
use Livewire\Component;
use Modules\Setup\Services\Contracts\AppSetupService;
use Modules\Shared\Livewire\Concerns\HandlesWizardSteps;

/**
 * Represents the 'System & SMTP' setup step in the application setup process.
 * This component handles capturing initial mail configuration.
 */
class SystemSetup extends Component
{
    use HandlesWizardSteps;

    public string $mail_host = '';

    public string $mail_port = '587';

    public string $mail_username = '';

    public string $mail_password = '';

    public string $mail_encryption = 'tls';

    public string $mail_from_address = '';

    public string $mail_from_name = '';

    /**
     * Boots the component and injects the AppSetupService.
     */
    public function boot(AppSetupService $setupService): void
    {
        $this->setupService = $setupService;
    }

    /**
     * Mounts the component and initializes properties.
     */
    public function mount(): void
    {
        $this->initWizardStepProps(
            currentStep: AppSetupService::STEP_SYSTEM,
            nextStep: AppSetupService::STEP_COMPLETE,
            prevStep: AppSetupService::STEP_INTERNSHIP,
        );

        $this->requireWizardAccess();

        $this->mail_host = (string) setting('mail_host', '');
        $this->mail_port = (string) setting('mail_port', '587');
        $this->mail_username = (string) setting('mail_username', '');
        $this->mail_password = (string) setting('mail_password', '');
        $this->mail_encryption = (string) setting('mail_encryption', 'tls');
        $this->mail_from_address = (string) setting('mail_from_address', 'no-reply@internara.test');
        $this->mail_from_name =
            (string) (setting('mail_from_name') ?: setting('brand_name', setting('app_name')));
    }

    /**
     * Skips the SMTP configuration and proceeds to the next step.
     */
    public function skip(): void
    {
        $this->nextStep();
    }

    /**
     * Saves the settings and proceeds to the next step.
     */
    public function save(): void
    {
        $this->validate([
            'mail_host' => 'required|string',
            'mail_port' => 'required|numeric',
            'mail_username' => 'nullable|string',
            'mail_password' => 'nullable|string',
            'mail_encryption' => 'nullable|string',
            'mail_from_address' => 'required|email',
            'mail_from_name' => 'required|string',
        ]);

        try {
            $this->setupService->saveSystemSettings([
                'mail_host' => $this->mail_host,
                'mail_port' => $this->mail_port,
                'mail_username' => $this->mail_username,
                'mail_password' => $this->mail_password,
                'mail_encryption' => $this->mail_encryption,
                'mail_from_address' => $this->mail_from_address,
                'mail_from_name' => $this->mail_from_name,
            ]);

            $this->nextStep();
        } catch (\Exception $e) {
            report($e);
            flash()->error(__('ui::errors.unexpected_technical_failure'));
        }
    }

    /**
     * Renders the component view.
     */
    public function render(): View
    {
        return view('setup::livewire.system-setup')->layout('setup::components.layouts.setup', [
            'title' =>
                __('setup::wizard.system.headline') .
                ' | ' .
                setting('site_title', setting('app_name')),
        ]);
    }
}
