<?php

declare(strict_types=1);

namespace App\User\SuperAdmin\Actions;

use App\Core\Actions\BaseAction;
use App\User\Enums\AccountStatus;
use App\User\Enums\Role;
use App\User\Models\User;
use Illuminate\Support\Facades\Hash;

final class InitializeSuperAdminAction extends BaseAction
{
    public function execute(string $email, string $password): User
    {
        return $this->transaction(function () use ($email, $password) {
            $adminName = config('setup.defaults.admin_name', 'Administrator');
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
