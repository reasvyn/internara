<?php

declare(strict_types=1);

namespace Modules\Setup\Livewire;

use Illuminate\View\View;
use Livewire\Component;
use Modules\Setup\Services\Contracts\SetupService;
use Modules\Shared\Rules\Turnstile;

/**
 * Represents the 'System & SMTP' setup step in the application setup process.
 * This component handles capturing initial mail configuration.
 */
class SystemSetup extends Component
{
    use Concerns\HandlesSetupSteps;

    public string $mail_host = '';

    public string $mail_port = '587';

    public string $mail_username = '';

    public string $mail_password = '';

    public string $mail_encryption = 'tls';

    public string $mail_from_address = '';

    public string $mail_from_name = '';

    /**
     * Turnstile token for S1 security compliance.
     */
    public ?string $turnstile = null;

    /**
     * Honeypot field for bot protection.
     */
    public ?string $contact_me = null;

    /**
     * Boots the component and injects the SetupService.
     */
    public function boot(SetupService $setupService): void
    {
        $this->setupService = $setupService;
    }

    /**
     * Mounts the component and initializes properties.
     */
    public function mount(): void
    {
        $this->initSetupStepProps(
            currentStep: SetupService::STEP_SYSTEM,
            nextStep: SetupService::STEP_COMPLETE,
            prevStep: SetupService::STEP_INTERNSHIP,
        );

        $this->requireSetupAccess();

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
     * Tests the SMTP connection with the provided settings.
     */
    public function testConnection(): void
    {
        $this->validate([
            'mail_host' => 'required|string',
            'mail_port' => 'required|numeric',
            'mail_encryption' => 'nullable|string',
        ]);

        try {
            $socket = @fsockopen($this->mail_host, (int) $this->mail_port, $errno, $errstr, 5);

            if ($socket) {
                fclose($socket);
                flash()->success(__('setup::wizard.system.smtp_connection_success'));
            } else {
                throw new \Exception($errstr ?: 'Connection timed out.');
            }
        } catch (\Exception $e) {
            flash()->error(
                __('setup::wizard.system.smtp_connection_failed', ['message' => $e->getMessage()]),
            );
        }
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
        $validated = $this->validate([
            'turnstile' => [
                config('services.cloudflare.turnstile.site_key') ? 'required' : 'nullable',
                new Turnstile,
            ],
            'contact_me' => [new \Modules\Shared\Rules\Honeypot],
            'mail_host' => 'required|string',
            'mail_port' => 'required|numeric',
            'mail_username' => 'nullable|string',
            'mail_password' => 'nullable|string',
            'mail_encryption' => 'nullable|string',
            'mail_from_address' => 'required|email',
            'mail_from_name' => 'required|string',
        ]);

        $this->setupService->saveSystemSettings($validated);

        $this->nextStep();
    }

    /**
     * Renders the component view.
     */
    public function render(): View
    {
        return view('setup::livewire.system-setup')->layout('setup::components.layouts.setup', [
            'title' => __('setup::wizard.system.headline').
                ' | '.
                setting('site_title', setting('app_name')),
        ]);
    }
}
