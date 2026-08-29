<?php

declare(strict_types=1);

namespace App\Modules\User\Domain\UserManagement\Actions;

use App\Modules\Core\Actions\BaseCommandAction;
use App\Modules\Core\Exceptions\RejectedException;
use App\Modules\User\Domain\UserManagement\Data\UpdateUserData;
use App\Modules\User\Domain\UserManagement\Events\UserUpdated;
use App\Modules\User\Models\User;
use App\Modules\User\Rules\ReservedAuthoritativeName;
use App\Modules\User\Rules\SystemUsername;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use RuntimeException;

/**
 * S1 - Secure: Atomic user update with profile and role sync.
 * S2 - Sustain: Proper error handling and logging.
 */
final class UpdateUserAction extends BaseCommandAction
{
    /**
     * Update an existing user.
     *
     * @throws RuntimeException when update fails
     */
    public function execute(UpdateUserData $data): User
    {
        $user = User::findOrFail($data->userId);
        $userData = $data->user;

        $integrity = $user->asSuperAdminIntegrityRules();

        if (isset($userData['name']) && ! $integrity->canChangeName()) {
            throw new RejectedException(__('profile.cannot_change_super_admin_name'));
        }

        if (isset($userData['username']) && ! $integrity->canChangeUsername()) {
            throw new RejectedException(__('profile.cannot_change_super_admin_username'));
        }

        $this->validateUserData($userData, $user);

        return $this->transaction(function () use ($user, $data) {
            $userData = $data->user;

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

            if ($data->profile !== null && $data->profile !== []) {
                $user->profile()->updateOrCreate(['user_id' => $user->id], $data->profile);
            }

            if ($data->roles !== null) {
                $user->syncRoles($data->roles);
            }

            $this->log('user_updated', $user, [
                'email' => $user->email,
                'roles' => $data->roles,
            ]);

            event(new UserUpdated($user));

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
