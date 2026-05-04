<?php

declare(strict_types=1);

namespace App\Domain\User\Actions;

use App\Domain\Core\Actions\LogAuditAction;
use App\Domain\User\Models\User;
use App\Domain\User\Rules\SystemUsername;
use App\Domain\User\Support\HandlesActionErrors;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use RuntimeException;

/**
 * S1 - Secure: Atomic user update with profile and role sync.
 * S2 - Sustain: Proper error handling and logging.
 */
class UpdateUserAction
{
    use HandlesActionErrors;

    public function __construct(protected readonly LogAuditAction $logAuditAction) {}

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
        $this->validateUserData($userData, $user);

        return $this->withErrorHandling(function () use ($user, $userData, $profileData, $roles) {
            return DB::transaction(function () use ($user, $userData, $profileData, $roles) {
                $updateData = array_filter(
                    [
                        'name' => $userData['name'] ?? null,
                        'email' => $userData['email'] ?? null,
                        'username' => $userData['username'] ?? null,
                        'password' => isset($userData['password'])
                            ? Hash::make($userData['password'])
                            : null,
                        'setup_required' => $userData['setup_required'] ?? null,
                        'locked_at' => array_key_exists('locked_at', $userData) ? $userData['locked_at'] : null,
                        'locked_reason' => array_key_exists('locked_reason', $userData) ? $userData['locked_reason'] : null,
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

                $this->logAuditAction->execute(
                    action: 'user_updated',
                    subjectType: User::class,
                    subjectId: $user->id,
                    payload: [
                        'email' => $user->email,
                        'roles' => $roles,
                    ],
                    module: 'Auth',
                );

                return $user;
            });
        }, 'Failed to update user');
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
            $rules['name'] = ['required', 'string', 'max:255'];
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
