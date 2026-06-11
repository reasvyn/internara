<?php

declare(strict_types=1);

namespace App\Auth\SuperAdmin\Console\Commands;

use App\Auth\SuperAdmin\Actions\InitializeSuperAdminAction;
use App\Core\Support\AppInfo;
use App\Setup\Entities\SetupEntity;
use App\User\Models\User;
use App\User\UserManagement\Actions\SaveRecoveryKeyAction;
use Illuminate\Console\Command;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

class CreateAdminCommand extends Command
{
    protected $signature = 'admin:create {email?} {password?}';

    public function __construct(
        private InitializeSuperAdminAction $action,
        private SaveRecoveryKeyAction $saveRecoveryKey,
    ) {
        parent::__construct();
        $this->description = __('superadmin.create.description');
    }

    public function handle(): int
    {
        $this->displayHeader();

        if ($this->hasSuperAdmin()) {
            $this->displayError(__('superadmin.create.already_exists'));

            return self::FAILURE;
        }

        $this->displayGuide();

        $email =
            $this->argument('email') ??
            text(
                label: __('superadmin.field_email'),
                required: true,
                validate: fn (string $value) => ! filter_var($value, FILTER_VALIDATE_EMAIL)
                    ? __('superadmin.create.invalid_email')
                    : null,
            );

        $password =
            $this->argument('password') ??
            password(
                label: __('superadmin.field_password'),
                required: true,
                validate: fn (string $value) => strlen($value) < 8
                    ? __('superadmin.create.password_min')
                    : null,
            );

        if ($this->argument('password') === null) {
            $confirm = password(
                label: __('superadmin.field_confirm_password'),
                required: true,
            );

            if ($password !== $confirm) {
                $this->displayError(__('superadmin.create.password_mismatch'));

                return self::FAILURE;
            }
        }

        $this->newLine();

        try {
            $user = $this->action->execute(email: $email, password: $password);

            $plaintext = $this->generateRecoveryKey();

            $this->displayResult($user, $plaintext);

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->displayError(
                __('setup.cli.admin_creation_failed', ['message' => $e->getMessage()]),
            );

            return self::FAILURE;
        }
    }

    private function generateRecoveryKey(): string
    {
        $keyLength = (int) config('setup.recovery_key.length', 64);
        $plaintext = Str::random($keyLength);

        SetupEntity::update(['install_recovery_key' => Hash::make($plaintext)]);

        try {
            $this->saveRecoveryKey->execute($plaintext);
        } catch (\Throwable) {
            $this->components->warn(__('superadmin.create.recovery_file_failed'));
        }

        return $plaintext;
    }

    private function displayHeader(): void
    {
        $this->newLine();
        $this->line('  <fg=white;options=bold;bg=blue> '.__('superadmin.title').' </>');
        $this->line(
            '  <fg=blue>'.
                __('superadmin.create.subtitle').
                '</> <fg=gray>'.
                __('superadmin.version', ['version' => AppInfo::version()]).
                '</>',
        );
        $this->newLine();
    }

    private function displayGuide(): void
    {
        $this->line('  <fg=gray>'.__('superadmin.create.guide').'</>');
        $this->newLine();
    }

    private function displayError(string $message): void
    {
        $this->newLine();
        $this->line('  <fg=white;options=bold;bg=red> ERROR </>');
        $this->line('  <fg=red>'.$message.'</>');
    }

    private function hasSuperAdmin(): bool
    {
        try {
            return User::role('super_admin')->exists();
        } catch (QueryException) {
            return false;
        }
    }

    private function displayResult(User $user, string $recoveryKey): void
    {
        $this->newLine();
        $this->components->info(__('superadmin.create.success'));
        $this->newLine();
        $this->line(
            '  <fg=yellow>'.
                __('superadmin.field_email_result').
                '</>  <fg=cyan>'.
                $user->email.
                '</>',
        );
        $this->line(
            '  <fg=yellow>'.
                __('superadmin.field_username').
                '</> <fg=cyan>'.
                $user->username.
                '</>',
        );
        $this->newLine();
        $this->line('  <fg=white;options=bold;bg=yellow> '.mb_strtoupper(__('superadmin.create.recovery_key_title')).' </>');
        $this->line('  <fg=yellow>'.__('superadmin.create.recovery_key_desc').'</>');
        $this->newLine();
        $this->line('  <fg=black;bg=yellow> '.$recoveryKey.' </>');
        $this->newLine();
        $this->components->warn(__('superadmin.create.change_password'));
    }
}
