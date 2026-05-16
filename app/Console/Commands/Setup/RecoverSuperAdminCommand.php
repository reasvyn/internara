<?php

declare(strict_types=1);

namespace App\Console\Commands\Setup;

use App\Actions\Setup\RecoverSuperAdminAction;
use App\Console\Commands\Setup\Traits\InteractsWithInstallerCli;
use App\Models\Setup;
use App\Models\User;
use App\Support\SmartLogger;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

use function Laravel\Prompts\error;
use function Laravel\Prompts\info;
use function Laravel\Prompts\password;
use function Laravel\Prompts\text;
use function Laravel\Prompts\warning;

class RecoverSuperAdminCommand extends Command
{
    use InteractsWithInstallerCli;

    protected $signature = 'setup:recover-super-admin {email?} {--reset} {--key=}';

    public function __construct(
        private RecoverSuperAdminAction $action,
    ) {
        parent::__construct();
        $this->description = __('setup.cli.recover_description');
    }

    public function handle(): int
    {
        $this->displayBanner();

        if (! $this->isInstalled()) {
            error(__('setup.cli.not_installed'));

            return self::FAILURE;
        }

        $email = $this->argument('email') ?? text(
            label: __('setup.cli.admin.email'),
            required: true,
            validate: fn (string $value) => ! filter_var($value, FILTER_VALIDATE_EMAIL) ? __('setup.cli.validation.invalid_email') : null,
        );

        if (! $this->verifyRecoveryKey()) {
            return self::FAILURE;
        }

        $isReset = $this->option('reset');
        $userExists = User::where('email', $email)->exists();

        if (! $isReset && $userExists) {
            SmartLogger::warning('super_admin_recovery_blocked_exists')
                ->module('setup')
                ->event('super_admin.recovery.blocked_already_exists')
                ->withPayload(['email' => $email])
                ->withPiiMasking()
                ->systemOnly()
                ->save();

            error(__('setup.cli.admin_already_exists', ['email' => $email]));

            return self::FAILURE;
        }

        if ($isReset && ! $userExists) {
            SmartLogger::warning('super_admin_recovery_blocked_not_found')
                ->module('setup')
                ->event('super_admin.recovery.blocked_not_found')
                ->withPayload(['email' => $email])
                ->withPiiMasking()
                ->systemOnly()
                ->save();

            error(__('setup.cli.admin_not_found', ['email' => $email]));

            return self::FAILURE;
        }

        $password = password(
            label: $isReset ? __('setup.cli.admin.new_password') : __('setup.cli.admin.password'),
            required: true,
            validate: fn (string $value) => strlen($value) < 8 ? __('setup.cli.validation.password_min') : null,
        );

        $confirmPassword = password(
            label: __('setup.cli.admin.confirm_password'),
            required: true,
        );

        if ($password !== $confirmPassword) {
            error(__('setup.cli.password_mismatch'));

            return self::FAILURE;
        }

        if (! $this->confirmRecovery($email, $isReset)) {
            return self::FAILURE;
        }

        try {
            $user = $this->action->execute(
                email: $email,
                password: $password,
                isReset: (bool) $isReset,
            );

            SmartLogger::success('super_admin_recovered')
                ->module('setup')
                ->event('super_admin.recovery.succeeded')
                ->withPayload(['email' => $email, 'mode' => $isReset ? 'reset' : 'create'])
                ->withPiiMasking()
                ->systemOnly()
                ->save();

            $this->displayCredentials($user, $password, $isReset);

            return self::SUCCESS;
        } catch (\Throwable $e) {
            SmartLogger::error('super_admin_recovery_failed')
                ->module('setup')
                ->event('super_admin.recovery.failed')
                ->withPayload(['error' => $e->getMessage()])
                ->systemOnly()
                ->save();

            error(__('setup.cli.installation_failed', ['message' => $e->getMessage()]));

            return self::FAILURE;
        }
    }

    private function verifyRecoveryKey(): bool
    {
        $key = $this->option('key');

        if ($key === null || $key === '') {
            error(__('setup.cli.recovery_key_required'));

            return false;
        }

        $storedSetup = Setup::latest('created_at')->first();
        $storedKey = $storedSetup?->recovery_key;
        $keyValid = false;

        if ($storedKey !== null) {
            try {
                $keyValid = hash_equals(Crypt::decryptString($storedKey), $key);
            } catch (\Throwable $e) {
                Log::warning('Recovery key decryption failed', ['error' => $e->getMessage()]);
                $keyValid = false;
            }
        }

        if (! $keyValid) {
            SmartLogger::warning('super_admin_recovery_invalid_key')
                ->module('setup')
                ->event('super_admin.recovery.invalid_key')
                ->systemOnly()
                ->save();

            error(__('setup.cli.recovery_key_invalid'));

            return false;
        }

        return true;
    }

    private function confirmRecovery(string $email, bool $isReset): bool
    {
        $mode = $isReset ? __('setup.cli.reset_mode') : __('setup.cli.create_mode');

        warning(__('setup.cli.recovery_confirmation_warning', ['mode' => $mode, 'email' => $email]));

        $confirmation = text(
            label: __('setup.cli.recovery_confirmation_prompt'),
            required: true,
        );

        if ($confirmation !== $email) {
            error(__('setup.cli.recovery_aborted'));

            return false;
        }

        return true;
    }

    private function displayCredentials(User $user, string $password, bool $isReset): void
    {
        $this->newLine();
        $message = $isReset ? __('setup.cli.recovery_success') : __('setup.cli.creation_success');
        info($message);
        $this->line("  Email: <fg=cyan>{$user->email}</>");
        $this->line("  Username: <fg=cyan>{$user->username}</>");
        $this->line("  Password: <fg=yellow>{$password}</>");
        $this->newLine();
        warning(__('setup.cli.change_password_warning'));
    }
}
