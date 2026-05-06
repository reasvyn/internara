<?php

declare(strict_types=1);

namespace App\Console\Commands\Setup;

use App\Actions\Setup\InitializeSuperAdminAction;
use App\Domain\Setup\Data\InitializeSuperAdminData;
use App\Domain\User\Models\User;
use App\Models\Setup;
use Illuminate\Console\Command;

use function Laravel\Prompts\error;
use function Laravel\Prompts\info;
use function Laravel\Prompts\password;
use function Laravel\Prompts\text;
use function Laravel\Prompts\warning;

class SuperAdminCommand extends Command
{
    use \App\Console\Commands\Setup\Traits\InteractsWithInstallerCli;

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
            error(__('setup.cli.not_installed'));

            return self::FAILURE;
        }

        // Check existing super admins
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
            $data = new InitializeSuperAdminData(
                email: $email,
                password: $password,
                name: $name,
                username: $username ?: null,
            );

            $user = $this->action->execute($data);

            $this->displayCredentials($user, $password);

            return self::SUCCESS;
        } catch (\Throwable $e) {
            error(__('setup.cli.admin_creation_failed', ['message' => $e->getMessage()]));

            return self::FAILURE;
        }
    }

    private function isInstalled(): bool
    {
        return file_exists(base_path('.installed')) ||
               Setup::where('is_installed', true)->exists();
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
