<?php

declare(strict_types=1);

namespace App\Domain\Admin\Console\Commands;

use App\Domain\Settings\Support\AppInfo;
use App\Domain\Setup\Actions\InitializeSuperAdminAction;
use App\Domain\User\Models\User;
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
        $this->description = __('admin.create.description');
    }

    public function handle(): int
    {
        $this->displayHeader();

        if ($this->hasSuperAdmin()) {
            $this->displayError(__('admin.create.already_exists'));

            return self::FAILURE;
        }

        $this->displayGuide();

        $email = $this->argument('email') ?? text(
            label: __('admin.field_email'),
            required: true,
            validate: fn (string $value) => ! filter_var($value, FILTER_VALIDATE_EMAIL) ? __('admin.create.invalid_email') : null,
        );

        $password = $this->argument('password') ?? password(
            label: __('admin.field_password'),
            required: true,
            validate: fn (string $value) => strlen($value) < 8 ? __('admin.create.password_min') : null,
        );

        $this->newLine();

        try {
            $user = $this->action->execute(
                email: $email,
                password: $password,
                name: config('setup.defaults.admin_name', 'Administrator'),
                username: 'superadmin',
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
        $this->line('  <fg=white;options=bold;bg=blue> '.__('admin.title').' </>');
        $this->line('  <fg=blue>'.__('admin.create.subtitle').'</> <fg=gray>'.__('admin.version', ['version' => AppInfo::version()]).'</>');
        $this->newLine();
    }

    private function displayGuide(): void
    {
        $this->line('  <fg=gray>'.__('admin.create.guide').'</>');
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
        $this->components->info(__('admin.create.success'));
        $this->newLine();
        $this->line('  <fg=yellow>'.__('admin.field_email_result').'</>  <fg=cyan>'.$user->email.'</>');
        $this->line('  <fg=yellow>'.__('admin.field_username').'</> <fg=cyan>'.$user->username.'</>');
        $this->newLine();
        $this->components->warn(__('admin.create.change_password'));
    }
}
