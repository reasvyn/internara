<?php

declare(strict_types=1);

namespace App\Console\Commands\Setup;

use App\Actions\Setup\RecoverAdminAccessAction;
use App\Domain\Setup\Data\RecoverAdminData;
use App\Domain\User\Models\User;
use App\Models\Setup;
use Illuminate\Console\Command;

use function Laravel\Prompts\error;
use function Laravel\Prompts\info;
use function Laravel\Prompts\password;
use function Laravel\Prompts\text;
use function Laravel\Prompts\warning;

class RecoverAdminCommand extends Command
{
    use \App\Console\Commands\Setup\Traits\InteractsWithInstallerCli;

    protected $signature = 'setup:recover-admin {email?} {--reset} {--role=super_admin}';

    public function __construct(
        private RecoverAdminAccessAction $action,
    ) {
        parent::__construct();
        $this->description = __('setup.cli.banner_subtitle');
    }

    public function handle(): int
    {
        $this->displayBanner();

        // Check system state
        if (! $this->isInstalled()) {
            error(__('setup.cli.not_installed'));

            return self::FAILURE;
        }

        $email = $this->argument('email') ?? text(
            label: __('setup.cli.admin.email'),
            required: true,
            validate: fn (string $value) => ! filter_var($value, FILTER_VALIDATE_EMAIL) ? __('setup.cli.validation.invalid_email') : null,
        );

        $isReset = $this->option('reset');

        // Determine operation
        $userExists = User::where('email', $email)->exists();

        if (! $isReset && $userExists) {
            error(__('setup.cli.admin_already_exists', ['email' => $email]));

            return self::FAILURE;
        }

        if ($isReset && ! $userExists) {
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

        try {
            $data = new RecoverAdminData(
                email: $email,
                password: $password,
                isReset: (bool) $isReset,
                role: $this->option('role'),
            );

            $user = $this->action->execute($data);

            $this->displayCredentials($user, $password, $isReset);

            return self::SUCCESS;
        } catch (\Throwable $e) {
            error(__('setup.cli.installation_failed', ['message' => $e->getMessage()]));

            return self::FAILURE;
        }
    }

    private function isInstalled(): bool
    {
        return file_exists(base_path('.installed')) ||
               Setup::where('is_installed', true)->exists();
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
