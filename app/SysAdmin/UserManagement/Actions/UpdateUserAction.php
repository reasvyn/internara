<?php

declare(strict_types=1);

namespace App\SysAdmin\UserManagement\Actions;

use App\Core\Actions\BaseAction;
use App\Core\Exceptions\RejectedException;
use App\Auth\SuperAdmin\Entities\SuperAdminIntegrityRules;
use App\User\Models\User;
use App\User\Rules\ReservedAuthoritativeName;
use App\User\Rules\SystemUsername;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use RuntimeException;

/**
 * S1 - Secure: Atomic user update with profile and role sync.
 * S2 - Sustain: Proper error handling and logging.
 */
final class UpdateUserAction extends BaseAction
{
    /**
     * Update an existing user.
     *
     * @param array<string, mixed> $userData
     * @param array<string, mixed>|null $profileData
     * @param list<string>|null $roles
     *
     * @throws RuntimeException when update fails
     */
    public function execute(
        User $user,
        array $userData,
        ?array $profileData = null,
        ?array $roles = null,
    ): User {
        $integrity = SuperAdminIntegrityRules::fromModel($user);

        if (isset($userData['name']) && ! $integrity->canChangeName()) {
            throw new RejectedException('Cannot change super admin name.');
        }

        if (isset($userData['username']) && ! $integrity->canChangeUsername()) {
            throw new RejectedException('Cannot change super admin username.');
        }

        $this->validateUserData($userData, $user);

        return $this->transaction(function () use ($user, $userData, $profileData, $roles) {
            $updateData = array_filter(
                [
                    'name' => $userData['name'] ?? null,
                    'email' => $userData['email'] ?? null,
                    'username' => $userData['username'] ?? null,
                    'password' => isset($userData['password'])
                        ? Hash::make($userData['password'])
                        : null,
                    'setup_required' => $userData['setup_required'] ?? null,
                    'locked_at' => array_key_exists('locked_at', $userData)
                        ? $userData['locked_at']
                        : null,
                    'locked_reason' => array_key_exists('locked_reason', $userData)
                        ? $userData['locked_reason']
                        : null,
                ],
                fn ($v) => $v !== null,
            );

            if ($updateData !== []) {
                $user->update($updateData);
            }

            if ($profileData !== null && $profileData !== []) {
                $user->profile()->updateOrCreate(['user_id' => $user->id], $profileData);
            }

            if ($roles !== null) {
                $user->syncRoles($roles);
            }

            $this->log('user_updated', $user, [
                'email' => $user->email,
                'roles' => $roles,
            ]);

            return $user;
        });
    }

    /**
     * Validate user data before update.
     *
     * @param array<string, mixed> $userData
     */
    protected function validateUserData(array $userData, User $user): void
    {
        $rules = [];

        if (isset($userData['name'])) {
            $rules['name'] = ['required', 'string', 'max:255', new ReservedAuthoritativeName];
        }

        if (isset($userData['username'])) {
            $rules['username'] = [
                'required',
                'string',
                'unique:users,username,'.$user->id,
                new SystemUsername,
            ];
        }

        if (isset($userData['email'])) {
            $rules['email'] = ['required', 'email', 'unique:users,email,'.$user->id];
        }

        if ($rules !== []) {
            Validator::make($userData, $rules)->validate();
        }
    }
}
