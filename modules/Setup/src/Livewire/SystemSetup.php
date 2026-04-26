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
        // [S1 - Secure] Rate limit connection tests to prevent SMTP amplification attacks
        $key = 'setup_smtp_test:' . request()->ip();
        if (\Illuminate\Support\Facades\RateLimiter::tooManyAttempts($key, 5)) {
            flash()->error(__('ui::messages.too_many_requests', ['seconds' => \Illuminate\Support\Facades\RateLimiter::availableIn($key)]));
            return;
        }
        \Illuminate\Support\Facades\RateLimiter::hit($key, 60);

        $this->validate([
            'mail_host' => 'required|string',
            'mail_port' => 'required|numeric',
            'mail_encryption' => 'nullable|string',
        ]);

        try {
            // Enterprise Grade: Real SMTP handshake check
            $timeout = 5;
            $socket = @fsockopen($this->mail_host, (int) $this->mail_port, $errno, $errstr, $timeout);

            if ($socket) {
                $response = fgets($socket, 1024);
                fclose($socket);
                
                if (str_starts_with((string) $response, '220')) {
                    flash()->success(__('setup::wizard.system.smtp_connection_success'));
                } else {
                    throw new \Exception("Server responded with: " . trim((string) $response));
                }
            } else {
                throw new \Exception($errstr ?: 'Connection timed out after ' . $timeout . ' seconds.');
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
        // [S1 - Secure] Bot & Amplification Protection
        $this->validate([
            'turnstile' => [new Turnstile],
            'contact_me' => [new \Modules\Shared\Rules\Honeypot],
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
            'title' => __('setup::wizard.system.headline').
                ' | '.
                setting('site_title', setting('app_name')),
        ]);
    }
}
