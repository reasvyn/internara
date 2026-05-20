<?php

declare(strict_types=1);

namespace App\Domain\Setup\Actions;

use App\Domain\Auth\Enums\Role as RoleEnum;
use App\Domain\Core\Actions\BaseAction;
use App\Domain\User\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class SetupSuperAdminAction extends BaseAction
{
    public function execute(array $data): User
    {
        Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:8'],
        ])->validate();

        return $this->withErrorHandling(function () use ($data) {
            return $this->transaction(function () use ($data) {
                $user = User::updateOrCreate(
                    ['username' => $data['username']],
                    [
                        'name' => $data['name'],
                        'email' => $data['email'],
                        'password' => Hash::make($data['password']),
                        'setup_required' => false,
                    ],
                );

                $user->markEmailAsVerified();

                $user->assignRole(RoleEnum::SUPER_ADMIN->value);

                $this->log('super_admin_created', $user, ['username' => $data['username']]);

                return $user;
            });
        }, 'Failed to setup super admin');
    }
}
