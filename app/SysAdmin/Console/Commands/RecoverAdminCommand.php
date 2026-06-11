<?php

declare(strict_types=1);

namespace App\SysAdmin\Console\Commands;

use App\Auth\SuperAdmin\Actions\RecoverSuperAdminAction;
use App\Auth\SuperAdmin\Notifications\RecoveryOtpNotification;
use App\Core\Support\AppInfo;
use App\Core\Support\SmartLogger;
use App\Setup\Entities\SetupEntity;
use App\User\Models\User;
use App\User\UserManagement\Actions\ReadRecoveryKeyAction;
use App\User\UserManagement\Actions\SaveRecoveryKeyAction;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

class RecoverAdminCommand extends Command
{
    protected $signature = 'admin:recover {email?} {--key=} {--regenerate-file : Re-write the recovery key file from the provided --key}';

    public function __construct(
        private RecoverSuperAdminAction $action,
        private ReadRecoveryKeyAction $readRecoveryKey,
        private SaveRecoveryKeyAction $saveRecoveryKey,
    ) {
        parent::__construct();
        $this->description = __('sysadmin.recover.description');
    }

    public function handle(): int
    {
        $this->displayHeader();

        if ($this->option('key') === null && $this->readRecoveryKey->execute() !== null) {
            $this->components->info(__('sysadmin.recover.key_detected'));
        }

        if (! $this->verifyRecoveryKey()) {
            return self::FAILURE;
        }

        if ($this->requiresOtp()) {
            if (! $this->sendAndVerifyOtp()) {
                return self::FAILURE;
            }
        }

        $this->displayGuide();

        $email =
            $this->argument('email') ??
            text(
                label: __('sysadmin.field_email'),
                required: true,
                validate: fn (string $value) => ! filter_var($value, FILTER_VALIDATE_EMAIL)
                    ? __('sysadmin.recover.invalid_email')
                    : null,
            );

        $userExists = User::where('email', $email)->exists();

        if (! $userExists) {
            SmartLogger::warning('super_admin_recovery_blocked_not_found')
                ->module('setup')
                ->event('super_admin.recovery.blocked_not_found')
                ->withPayload(['email' => $email])
                ->withPiiMasking()
                ->systemOnly()
                ->save();

            $this->displayError(__('sysadmin.recover.not_found', ['email' => $email]));

            return self::FAILURE;
        }

        $password = password(
            label: __('sysadmin.field_new_password'),
            required: true,
            validate: fn (string $value) => strlen($value) < 8
                ? __('sysadmin.recover.password_min')
                : null,
        );

        $confirmPassword = password(label: __('sysadmin.field_confirm_password'), required: true);

        if ($password !== $confirmPassword) {
            $this->displayError(__('sysadmin.recover.password_mismatch'));

            return self::FAILURE;
        }

        $this->newLine();

        if (! $this->confirmRecovery($email)) {
            return self::FAILURE;
        }

        try {
            $user = $this->action->execute(
                email: $email,
                password: $password,
            );

            $plaintext = $this->regenerateRecoveryKey();

            SmartLogger::success('super_admin_recovered')
                ->module('setup')
                ->event('super_admin.recovery.succeeded')
                ->withPayload(['email' => $email, 'mode' => 'reset'])
                ->withPiiMasking()
                ->systemOnly()
                ->save();

            $this->displayResult($user, $plaintext);

            return self::SUCCESS;
        } catch (\Throwable $e) {
            SmartLogger::error('super_admin_recovery_failed')
                ->module('setup')
                ->event('super_admin.recovery.failed')
                ->withPayload(['error' => $e->getMessage()])
                ->systemOnly()
                ->save();

            $this->displayError(
                __('setup.cli.installation_failed', ['message' => $e->getMessage()]),
            );

            return self::FAILURE;
        }
    }

    private function requiresOtp(): bool
    {
        return app()->environment('production');
    }

    private function sendAndVerifyOtp(): bool
    {
        $otp = (string) random_int(100000, 999999);
        $email = $this->argument('email');

        if ($email === null) {
            $email = text(
                label: __('sysadmin.field_email'),
                required: true,
                validate: fn (string $value) => ! filter_var($value, FILTER_VALIDATE_EMAIL)
                    ? __('sysadmin.recover.invalid_email')
                    : null,
            );
        }

        $user = User::where('email', $email)->first();

        if ($user === null) {
            $this->displayError(__('sysadmin.recover.not_found', ['email' => $email]));

            return false;
        }

        Cache::put('recovery_otp_'.$email, Hash::make($otp), 300);

        try {
            $user->notify(new RecoveryOtpNotification($otp));
        } catch (\Throwable) {
            $this->displayError(__('sysadmin.recover.otp_send_failed'));

            return false;
        }

        $this->components->info(__('sysadmin.recover.otp_sent'));

        $input = text(
            label: __('sysadmin.recover.otp_prompt'),
            required: true,
            validate: function (string $value) use ($email) {
                $stored = Cache::get('recovery_otp_'.$email);

                if ($stored === null) {
                    return __('sysadmin.recover.otp_expired');
                }

                if (! Hash::check($value, $stored)) {
                    return __('sysadmin.recover.otp_invalid');
                }

                return null;
            },
        );

        Cache::forget('recovery_otp_'.$email);

        return true;
    }

    private function displayHeader(): void
    {
        $this->newLine();
        $this->line('  <fg=white;options=bold;bg=blue> '.__('sysadmin.title').' </>');
        $this->line(
            '  <fg=blue>'.
                __('sysadmin.recover.subtitle').
                '</> <fg=gray>'.
                __('sysadmin.version', ['version' => AppInfo::version()]).
                '</>',
        );
        $this->newLine();
    }

    private function displayGuide(): void
    {
        $this->line('  <fg=gray>'.__('sysadmin.recover.guide').'</>');
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
            $this->displayError(__('sysadmin.recover.key_required'));

            return false;
        }

        $storedHash = SetupEntity::get()->recoveryKey();
        $keyValid = $storedHash !== null && Hash::check($key, $storedHash);

        if (! $keyValid) {
            SmartLogger::warning('super_admin_recovery_invalid_key')
                ->module('setup')
                ->event('super_admin.recovery.invalid_key')
                ->systemOnly()
                ->save();

            $this->displayError(__('sysadmin.recover.key_invalid'));

            return false;
        }

        if ($this->option('regenerate-file')) {
            $path = $this->saveRecoveryKey->execute($key);
            $this->components->info(__('sysadmin.recover.file_regenerated', ['path' => $path]));
        }

        return true;
    }

    private function confirmRecovery(string $email): bool
    {
        $this->newLine();
        $this->components->warn(
            __('sysadmin.recover.confirm_warning', ['email' => $email]),
        );

        $confirmation = text(label: __('sysadmin.recover.confirm_prompt'), required: true);

        if ($confirmation !== $email) {
            $this->displayError(__('sysadmin.recover.aborted'));

            return false;
        }

        return true;
    }

    private function displayResult(User $user, string $recoveryKey): void
    {
        $this->newLine();
        $this->components->info(__('sysadmin.recover.success_reset'));
        $this->newLine();
        $this->line(
            '  <fg=yellow>'.
                __('sysadmin.field_email_result').
                '</>  <fg=cyan>'.
                $user->email.
                '</>',
        );
        $this->line(
            '  <fg=yellow>'.
                __('sysadmin.field_username').
                '</> <fg=cyan>'.
                $user->username.
                '</>',
        );
        $this->newLine();
        $this->line('  <fg=white;options=bold;bg=yellow> '.mb_strtoupper(__('sysadmin.recover.recovery_key_title')).' </>');
        $this->line('  <fg=yellow>'.__('sysadmin.recover.recovery_key_desc').'</>');
        $this->newLine();
        $this->line('  <fg=black;bg=yellow> '.$recoveryKey.' </>');
        $this->newLine();
        $this->components->warn(__('sysadmin.recover.change_password'));
    }

    private function regenerateRecoveryKey(): string
    {
        $keyLength = (int) config('setup.recovery_key.length', 64);
        $plaintext = Str::random($keyLength);

        SetupEntity::update(['install_recovery_key' => Hash::make($plaintext)]);

        try {
            $this->saveRecoveryKey->execute($plaintext);
        } catch (\Throwable) {
            $this->components->warn(__('sysadmin.recover.file_regenerated_failed'));
        }

        return $plaintext;
    }
}
