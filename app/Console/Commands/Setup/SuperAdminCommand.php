<?php

declare(strict_types=1);

namespace App\Console\Commands\Setup;

use App\Actions\Setup\InitializeSuperAdminAction;
use App\Console\Commands\Setup\Traits\InteractsWithInstallerCli;
use App\Models\Setup;
use App\Models\User;
use Illuminate\Console\Command;

use function Laravel\Prompts\error;
use function Laravel\Prompts\info;
use function Laravel\Prompts\password;
use function Laravel\Prompts\text;
use function Laravel\Prompts\warning;

class SuperAdminCommand extends Command
{
    use InteractsWithInstallerCli;

    protected $signature = 'setup:super-admin {email?} {password?} {--name=} {--username=}';

    public function __construct(
        private InitializeSuperAdminAction $action,
    ) {
        parent::__construct();
        $this->description = __('setup.cli.starting_installation');
    }

    public function handle(): int
    {
        $this->displayBanner();

        if (! $this->isInstalled()) {
            error(__('setup.cli.not_installed'));

            return self::FAILURE;
        }

        if ($this->hasSuperAdmin()) {
            error(__('setup.cli.admin_exists'));

            return self::FAILURE;
        }

        $email = $this->argument('email') ?? text(
            label: __('setup.cli.admin.email'),
            required: true,
            validate: fn (string $value) => ! filter_var($value, FILTER_VALIDATE_EMAIL) ? __('setup.cli.validation.invalid_email') : null,
        );

        $name = $this->option('name') ?? text(
            label: __('setup.cli.admin.name'),
            required: true,
        );

        $username = $this->option('username') ?? '';

        $password = $this->argument('password') ?? password(
            label: __('setup.cli.admin.password'),
            required: true,
            validate: fn (string $value) => strlen($value) < 8 ? __('setup.cli.validation.password_min') : null,
        );

        try {
            $user = $this->action->execute(
                email: $email,
                password: $password,
                name: $name,
                username: $username ?: null,
            );

            $this->displayCredentials($user, $password);

            return self::SUCCESS;
        } catch (\Throwable $e) {
            error(__('setup.cli.admin_creation_failed', ['message' => $e->getMessage()]));

            return self::FAILURE;
        }
    }

    private function isInstalled(): bool
    {
        return Setup::where('is_installed', true)->exists();
    }

    private function hasSuperAdmin(): bool
    {
        return User::role('super_admin')->exists();
    }

    private function displayCredentials(User $user, string $password): void
    {
        $this->newLine();
        info(__('setup.cli.creation_success'));
        $this->line("  Email: <fg=cyan>{$user->email}</>");
        $this->line("  Username: <fg=cyan>{$user->username}</>");
        $this->line("  Password: <fg=yellow>{$password}</>");
        $this->newLine();
        warning(__('setup.cli.change_password_warning'));
    }
}
