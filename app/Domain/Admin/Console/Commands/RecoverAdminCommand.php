<?php

declare(strict_types=1);

namespace App\Domain\Admin\Console\Commands;

use App\Domain\Admin\Actions\ReadRecoveryKeyAction;
use App\Domain\Admin\Actions\SaveRecoveryKeyAction;
use App\Domain\Core\Support\SmartLogger;
use App\Domain\Settings\Support\AppInfo;
use App\Domain\Setup\Actions\RecoverSuperAdminAction;
use App\Domain\Setup\Models\Setup;
use App\Domain\User\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

class RecoverAdminCommand extends Command
{
    protected $signature = 'admin:recover {email?} {--reset} {--key=} {--regenerate-file : Re-write the recovery key file from the provided --key}';

    public function __construct(
        private RecoverSuperAdminAction $action,
        private ReadRecoveryKeyAction $readRecoveryKey,
        private SaveRecoveryKeyAction $saveRecoveryKey,
    ) {
        parent::__construct();
        $this->description = __('admin.recover.description');
    }

    public function handle(): int
    {
        $this->displayHeader();

        if ($this->option('key') === null && $this->readRecoveryKey->execute() !== null) {
            $this->components->info(__('admin.recover.key_detected'));
        }

        if (! $this->verifyRecoveryKey()) {
            return self::FAILURE;
        }

        $this->displayGuide();

        $email = $this->argument('email') ?? text(
            label: __('admin.field_email'),
            required: true,
            validate: fn (string $value) => ! filter_var($value, FILTER_VALIDATE_EMAIL) ? __('admin.recover.invalid_email') : null,
        );

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

            $this->displayError(__('admin.recover.already_exists', ['email' => $email]));

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

            $this->displayError(__('admin.recover.not_found', ['email' => $email]));

            return self::FAILURE;
        }

        $password = password(
            label: $isReset ? __('admin.field_new_password') : __('admin.field_password'),
            required: true,
            validate: fn (string $value) => strlen($value) < 8 ? __('admin.recover.password_min') : null,
        );

        $confirmPassword = password(
            label: __('admin.field_confirm_password'),
            required: true,
        );

        if ($password !== $confirmPassword) {
            $this->displayError(__('admin.recover.password_mismatch'));

            return self::FAILURE;
        }

        $this->newLine();

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

            $this->displayResult($user, $isReset);

            return self::SUCCESS;
        } catch (\Throwable $e) {
            SmartLogger::error('super_admin_recovery_failed')
                ->module('setup')
                ->event('super_admin.recovery.failed')
                ->withPayload(['error' => $e->getMessage()])
                ->systemOnly()
                ->save();

            $this->displayError(__('setup.cli.installation_failed', ['message' => $e->getMessage()]));

            return self::FAILURE;
        }
    }

    private function displayHeader(): void
    {
        $this->newLine();
        $this->line('  <fg=white;options=bold;bg=blue> '.__('admin.title').' </>');
        $this->line('  <fg=blue>'.__('admin.recover.subtitle').'</> <fg=gray>'.__('admin.version', ['version' => AppInfo::version()]).'</>');
        $this->newLine();
    }

    private function displayGuide(): void
    {
        $this->line('  <fg=gray>'.__('admin.recover.guide').'</>');
        $this->newLine();
    }

    private function displayError(string $message): void
    {
        $this->newLine();
        $this->line('  <fg=white;options=bold;bg=red> ERROR </>');
        $this->line('  <fg=red>'.$message.'</>');
    }

    private function verifyRecoveryKey(): bool
    {
        $key = $this->option('key');

        if ($key === null || $key === '') {
            $key = $this->readRecoveryKey->execute();
        }

        if ($key === null || $key === '') {
            $this->displayError(__('admin.recover.key_required'));

            return false;
        }

        $storedSetup = Setup::latest('created_at')->first();
        $storedHash = $storedSetup?->recovery_key;
        $keyValid = $storedHash !== null && Hash::check($key, $storedHash);

        if (! $keyValid) {
            SmartLogger::warning('super_admin_recovery_invalid_key')
                ->module('setup')
                ->event('super_admin.recovery.invalid_key')
                ->systemOnly()
                ->save();

            $this->displayError(__('admin.recover.key_invalid'));

            return false;
        }

        if ($this->option('regenerate-file')) {
            $path = $this->saveRecoveryKey->execute($key);
            $this->components->info(__('admin.recover.file_regenerated', ['path' => $path]));
        }

        return true;
    }

    private function confirmRecovery(string $email, bool $isReset): bool
    {
        $mode = $isReset
            ? __('admin.recover.confirm_mode_reset')
            : __('admin.recover.confirm_mode_create');

        $this->newLine();
        $this->components->warn(__('admin.recover.confirm_warning', ['mode' => $mode, 'email' => $email]));

        $confirmation = text(
            label: __('admin.recover.confirm_prompt'),
            required: true,
        );

        if ($confirmation !== $email) {
            $this->displayError(__('admin.recover.aborted'));

            return false;
        }

        return true;
    }

    private function displayResult(User $user, bool $isReset): void
    {
        $this->newLine();
        $message = $isReset ? __('admin.recover.success_reset') : __('admin.recover.success_create');
        $this->components->info($message);
        $this->newLine();
        $this->line('  <fg=yellow>'.__('admin.field_email_result').'</>  <fg=cyan>'.$user->email.'</>');
        $this->line('  <fg=yellow>'.__('admin.field_username').'</> <fg=cyan>'.$user->username.'</>');
        $this->newLine();
        $this->components->warn(__('admin.recover.change_password'));
    }
}
