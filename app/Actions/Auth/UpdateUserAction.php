<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Actions\Audit\LogAuditAction;
use App\Models\User;
use App\Rules\SystemUsername;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

/**
 * S1 - Secure: Atomic user update with profile and role sync.
 */
class UpdateUserAction
{
    public function __construct(
        protected readonly LogAuditAction $logAuditAction
    ) {}

    /**
     * Update an existing user.
     */
    public function execute(User $user, array $userData, ?array $profileData = null, ?array $roles = null): User
    {
        if (isset($userData['username'])) {
            Validator::make($userData, [
                'username' => ['required', 'string', 'unique:users,username,'.$user->id, new SystemUsername],
            ])->validate();
        }

        return DB::transaction(function () use ($user, $userData, $profileData, $roles) {
            $user->update(array_filter([
                'name' => $userData['name'] ?? null,
                'email' => $userData['email'] ?? null,
                'username' => $userData['username'] ?? null,
                'password' => isset($userData['password']) ? Hash::make($userData['password']) : null,
                'setup_required' => $userData['setup_required'] ?? null,
            ], fn ($v) => $v !== null));

            if ($profileData !== null) {
                $user->profile()->updateOrCreate(
                    ['user_id' => $user->id],
                    $profileData
                );
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
                module: 'Auth'
            );

            return $user;
        });
    }
}
