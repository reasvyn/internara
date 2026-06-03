<?php

declare(strict_types=1);

namespace App\Domain\User\Aggregates\SuperAdmin\Actions;

use App\Domain\Core\Actions\BaseAction;
use App\Domain\Core\Exceptions\RejectedException;
use App\Domain\Core\Support\PasswordRules;
use App\Domain\User\Aggregates\SuperAdmin\Entities\SuperAdminIntegrityRules;
use App\Domain\User\Enums\AccountStatus;
use App\Domain\User\Enums\Role as RoleEnum;
use App\Domain\User\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

final class SetupSuperAdminAction extends BaseAction
{
    public function execute(string $email, string $password): User
    {
        Validator::make(['email' => $email, 'password' => $password], [
            'email' => ['required', 'email'],
            'password' => PasswordRules::defaultAsArray(),
        ])->validate();

        return $this->transaction(function () use ($email, $password) {
            $username = config('setup.defaults.admin_username', 'superadmin');
            $adminName = config('setup.defaults.admin_name', 'Administrator');

            $existing = User::where('username', $username)->first();

            if ($existing !== null) {
                $integrity = SuperAdminIntegrityRules::fromModel($existing);

                if ($integrity->isImmutable()) {
                    throw new RejectedException('Super admin already exists and cannot be re-initialized.');
                }
            }

            $user = User::updateOrCreate(
                ['username' => $username],
                [
                    'name' => $adminName,
                    'email' => $email,
                    'password' => Hash::make($password),
                    'setup_required' => false,
                ],
            );

            $user->markEmailAsVerified();

            $user->assignRole(RoleEnum::SUPER_ADMIN->value);
            $user->setStatus(AccountStatus::PROTECTED);

            $this->log('super_admin_created', $user, ['username' => $username]);

            return $user;
        });
    }
}
