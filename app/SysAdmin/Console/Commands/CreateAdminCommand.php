<?php

declare(strict_types=1);

namespace App\SysAdmin\Console\Commands;

use App\Settings\Support\AppInfo;
use App\User\Models\User;
use App\User\SuperAdmin\Actions\InitializeSuperAdminAction;
use Illuminate\Console\Command;

use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

class CreateAdminCommand extends Command
{
    protected $signature = 'admin:create {email?} {password?}';

    public function __construct(
        private InitializeSuperAdminAction $action,
    ) {
        parent::__construct();
        $this->description = __('sysadmin.create.description');
    }

    public function handle(): int
    {
        $this->displayHeader();

        if ($this->hasSuperAdmin()) {
            $this->displayError(__('sysadmin.create.already_exists'));

            return self::FAILURE;
        }

        $this->displayGuide();

        $email = $this->argument('email') ?? text(
            label: __('sysadmin.field_email'),
            required: true,
            validate: fn (string $value) => ! filter_var($value, FILTER_VALIDATE_EMAIL) ? __('sysadmin.create.invalid_email') : null,
        );

        $password = $this->argument('password') ?? password(
            label: __('sysadmin.field_password'),
            required: true,
            validate: fn (string $value) => strlen($value) < 8 ? __('sysadmin.create.password_min') : null,
        );

        $this->newLine();

        try {
            $user = $this->action->execute(
                email: $email,
                password: $password,
            );

            $this->displayResult($user);

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->displayError(__('setup.cli.admin_creation_failed', ['message' => $e->getMessage()]));

            return self::FAILURE;
        }
    }

    private function displayHeader(): void
    {
        $this->newLine();
        $this->line('  <fg=white;options=bold;bg=blue> '.__('sysadmin.title').' </>');
        $this->line('  <fg=blue>'.__('sysadmin.create.subtitle').'</> <fg=gray>'.__('sysadmin.version', ['version' => AppInfo::version()]).'</>');
        $this->newLine();
    }

    private function displayGuide(): void
    {
        $this->line('  <fg=gray>'.__('sysadmin.create.guide').'</>');
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
        return User::role('super_admin')->exists();
    }

    private function displayResult(User $user): void
    {
        $this->newLine();
        $this->components->info(__('sysadmin.create.success'));
        $this->newLine();
        $this->line('  <fg=yellow>'.__('sysadmin.field_email_result').'</>  <fg=cyan>'.$user->email.'</>');
        $this->line('  <fg=yellow>'.__('sysadmin.field_username').'</> <fg=cyan>'.$user->username.'</>');
        $this->newLine();
        $this->components->warn(__('sysadmin.create.change_password'));
    }
}
