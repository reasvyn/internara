<?php

declare(strict_types=1);

namespace App\Domain\Admin\Console\Commands;

use App\Domain\Auth\Enums\Role as RoleEnum;
use App\Domain\Core\Support\SmartLogger;
use App\Domain\User\Models\User;
use Illuminate\Console\Command;
use Spatie\Permission\Models\Role;

class AdminPromoteCommand extends Command
{
    protected $signature = 'admin:promote {identifier : Email or Username of the user} {--role=admin : Role to assign (admin/super_admin)}';

    protected $description = 'Promote a user to an administrative role';

    public function handle(): int
    {
        $identifier = $this->argument('identifier');
        $roleName = $this->option('role');

        $user = User::where('email', $identifier)->orWhere('username', $identifier)->first();

        if (! $user) {
            $this->components->error(__('admin.promote.user_not_found', ['identifier' => $identifier]));

            return Command::FAILURE;
        }

        if (! in_array($roleName, ['admin', 'super_admin'])) {
            $this->components->error(__('admin.promote.invalid_role', ['role' => $roleName]));

            return Command::FAILURE;
        }

        if (! Role::where('name', $roleName)->exists()) {
            $this->components->error(__('admin.promote.role_absent', ['role' => $roleName]));

            return Command::FAILURE;
        }

        if ($roleName === RoleEnum::SUPER_ADMIN->value) {
            $existingCount = User::role(RoleEnum::SUPER_ADMIN->value)->count();

            if ($existingCount > 0) {
                $this->components->error(__('admin.promote.super_admin_exists'));

                return Command::FAILURE;
            }
        }

        if ($user->hasRole($roleName)) {
            $this->components->warn(__('admin.promote.already_has_role', ['name' => $user->name, 'role' => $roleName]));

            return Command::SUCCESS;
        }

        $user->assignRole($roleName);

        $this->components->info(__('admin.promote.success', ['name' => $user->name, 'email' => $user->email, 'role' => $roleName]));

        SmartLogger::info('User promoted via CLI')
            ->withPayload([
                'email' => $user->email,
                'role' => $roleName,
            ])
            ->systemOnly()
            ->save();

        return Command::SUCCESS;
    }
}
