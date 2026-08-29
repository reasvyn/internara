<?php

declare(strict_types=1);

namespace App\Modules\Auth\Domain\SuperAdmin\Actions;

use App\Modules\Auth\Domain\Permissions\Enums\Role;
use App\Modules\Core\Actions\BaseCommandAction;
use App\Modules\User\Enums\AccountStatus;
use App\Modules\User\Models\User;
use Illuminate\Support\Facades\Hash;

final class InitializeSuperAdminAction extends BaseCommandAction
{
    public function execute(string $email, string $password): User
    {
        return $this->transaction(function () use ($email, $password) {
            $adminName = config('setup.defaults.admin_name', 'Super Admin');
            $username = config('setup.defaults.admin_username', 'superadmin');

            $user = User::create([
                'name' => $adminName,
                'email' => $email,
                'password' => Hash::make($password),
                'username' => $username,
            ]);

            $user->profile()->create();

            $user->assignRole(Role::SUPER_ADMIN);
            $user->setStatus(AccountStatus::PROTECTED);

            $this->log('super_admin_created', $user, [
                'email' => $user->email,
                'source' => 'cli',
            ]);

            return $user;
        });
    }
}
