<?php

declare(strict_types=1);

namespace App\Domain\Setup\Actions;

use App\Domain\Auth\Enums\AccountStatus;
use App\Domain\Auth\Enums\Role;
use App\Domain\Core\Actions\BaseAction;
use App\Domain\User\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

final class InitializeSuperAdminAction extends BaseAction
{
    public function execute(string $email, string $password, ?string $name = null, ?string $username = null): User
    {
        return $this->transaction(function () use ($email, $password, $name, $username) {
            $name ??= config('setup.defaults.admin_name', 'Administrator');

            $user = User::create([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make($password),
                'username' => $username ?? $this->generateUsername($name),
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

    private function generateUsername(string $name): string
    {
        $base = Str::slug($name, '');
        $maxLength = (int) config('setup.defaults.username_max_length', 20);

        return Str::limit($base, $maxLength, '');
    }
}
