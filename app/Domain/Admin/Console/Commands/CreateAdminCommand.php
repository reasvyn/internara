<?php

declare(strict_types=1);

namespace App\Domain\Admin\Console\Commands;

use App\Domain\Settings\Support\AppInfo;
use App\Domain\Setup\Actions\InitializeSuperAdminAction;
use App\Domain\User\Models\User;
use Illuminate\Console\Command;

use function Laravel\Prompts\error;
use function Laravel\Prompts\info;
use function Laravel\Prompts\intro;
use function Laravel\Prompts\note;
use function Laravel\Prompts\password;
use function Laravel\Prompts\text;
use function Laravel\Prompts\warning;

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
            error(__('admin.create.already_exists'));

            return self::FAILURE;
        }

        $this->displayGuide();
        note(__('admin.section_account'));

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

        $this->displaySeparator();

        try {
            $user = $this->action->execute(
                email: $email,
                password: $password,
                name: 'Super Administrator',
                username: 'superadmin',
            );

            $this->displayResult($user);

            return self::SUCCESS;
        } catch (\Throwable $e) {
            error(__('setup.cli.admin_creation_failed', ['message' => $e->getMessage()]));

            return self::FAILURE;
        }
    }

    private function displayHeader(): void
    {
        $this->newLine();
        intro(__('admin.title'));
        $this->line('  <fg=gray>'.__('admin.create.subtitle').'  '.__('admin.version', ['version' => AppInfo::version()]).'</>');
        $this->newLine();
    }

    private function displayGuide(): void
    {
        $this->line('  <fg=gray>'.__('admin.create.guide').'</>');
        $this->newLine();
    }

    private function displaySeparator(): void
    {
        $this->newLine();
        $this->line('  <fg=gray>'.str_repeat('─', 48).'</>');
        $this->newLine();
    }

    private function hasSuperAdmin(): bool
    {
        return User::role('super_admin')->exists();
    }

    private function displayResult(User $user): void
    {
        $this->newLine();
        info(__('admin.create.success'));
        $this->newLine();
        $this->line('  <fg=yellow>'.__('admin.field_email_result').'</>  <fg=cyan>'.$user->email.'</>');
        $this->line('  <fg=yellow>'.__('admin.field_username').'</> <fg=cyan>'.$user->username.'</>');
        $this->newLine();
        warning(__('admin.create.change_password'));
    }
}
