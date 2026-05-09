<?php

declare(strict_types=1);

namespace App\Console\Commands\Setup;

use App\Actions\Setup\RecoverAdminAccessAction;
use App\Console\Commands\Setup\Traits\InteractsWithInstallerCli;
use App\Models\Setup;
use App\Models\User;
use App\Support\SmartLogger;
use Illuminate\Console\Command;

use function Laravel\Prompts\error;
use function Laravel\Prompts\info;
use function Laravel\Prompts\password;
use function Laravel\Prompts\text;
use function Laravel\Prompts\warning;

class RecoverAdminCommand extends Command
{
    use InteractsWithInstallerCli;

    protected $signature = 'setup:recover-admin {email?} {--reset} {--role=super_admin} {--key=}';

    public function __construct(
        private RecoverAdminAccessAction $action,
    ) {
        parent::__construct();
        $this->description = __('setup.cli.banner_subtitle');
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
            SmartLogger::warning('admin_recovery_blocked_exists')
                ->module('Setup')
                ->event('admin.recovery.blocked_already_exists')
                ->withPayload(['email' => $email])
                ->withPiiMasking()
                ->systemOnly()
                ->save();

            error(__('setup.cli.admin_already_exists', ['email' => $email]));

            return self::FAILURE;
        }

        if ($isReset && ! $userExists) {
            SmartLogger::warning('admin_recovery_blocked_not_found')
                ->module('Setup')
                ->event('admin.recovery.blocked_not_found')
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

        if (! $this->confirmRecovery($email, $isReset, $this->option('role'))) {
            return self::FAILURE;
        }

        try {
            $user = $this->action->execute(
                email: $email,
                password: $password,
                isReset: (bool) $isReset,
                role: $this->option('role'),
            );

            SmartLogger::success('admin_recovered')
                ->module('Setup')
                ->event('admin.recovery.succeeded')
                ->withPayload(['email' => $email, 'role' => $this->option('role'), 'mode' => $isReset ? 'reset' : 'create'])
                ->withPiiMasking()
                ->systemOnly()
                ->save();

            $this->displayCredentials($user, $password, $isReset);

            return self::SUCCESS;
        } catch (\Throwable $e) {
            SmartLogger::error('admin_recovery_failed')
                ->module('Setup')
                ->event('admin.recovery.failed')
                ->withPayload(['error' => $e->getMessage()])
                ->systemOnly()
                ->save();

            error(__('setup.cli.installation_failed', ['message' => $e->getMessage()]));

            return self::FAILURE;
        }
    }

    private function isInstalled(): bool
    {
        return file_exists(base_path('.installed')) ||
               Setup::where('is_installed', true)->exists();
    }

    private function verifyRecoveryKey(): bool
    {
        $key = $this->option('key');

        if ($key === null || $key === '') {
            error(__('setup.cli.recovery_key_required'));

            return false;
        }

        if (! Setup::validateRecoveryKey($key)) {
            SmartLogger::warning('admin_recovery_invalid_key')
                ->module('Setup')
                ->event('admin.recovery.invalid_key')
                ->systemOnly()
                ->save();

            error(__('setup.cli.recovery_key_invalid'));

            return false;
        }

        return true;
    }

    private function confirmRecovery(string $email, bool $isReset, string $role): bool
    {
        $mode = $isReset ? __('setup.cli.reset_mode') : __('setup.cli.create_mode');

        warning(__('setup.cli.recovery_confirmation_warning', ['mode' => $mode, 'email' => $email, 'role' => $role]));

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
