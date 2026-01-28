<?php

declare(strict_types=1);

namespace Modules\Setup\Livewire;

use Illuminate\View\View;
use Livewire\Component;
use Modules\Setup\Services\Contracts\SetupService;

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
            currentStep: 'system',
            nextStep: 'complete',
            prevStep: 'internship',
        );

        $this->requireSetupAccess();

        $this->mail_from_name = setting('brand_name', 'Internara');
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
            $transport = match ($this->mail_encryption) {
                'ssl' => "ssl://{$this->mail_host}:{$this->mail_port}",
                'tls', 'starttls' => "tcp://{$this->mail_host}:{$this->mail_port}",
                default => "tcp://{$this->mail_host}:{$this->mail_port}",
            };

            $socket = @fsockopen($this->mail_host, (int) $this->mail_port, $errno, $errstr, 5);

            if ($socket) {
                fclose($socket);
                $this->dispatch('notify', type: 'success', message: 'SMTP Connection successful!');
            } else {
                throw new \Exception($errstr ?: 'Connection timed out.');
            }
        } catch (\Exception $e) {
            $this->dispatch(
                'notify',
                type: 'error',
                message: 'Connection failed: '.$e->getMessage(),
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
            'title' => __('setup::wizard.system.title').' | '.setting('site_title', 'Internara'),
        ]);
    }
}
