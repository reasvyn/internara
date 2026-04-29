<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Actions\Audit\LogAuditAction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * S1 - Secure: Atomic user creation with profile and role assignment.
 */
class CreateUserAction
{
    public function __construct(
        protected LogAuditAction $logAuditAction
    ) {}

    /**
     * Create a new user with associated profile and roles.
     */
    public function execute(array $userData, array $profileData = [], array $roles = []): User
    {
        return DB::transaction(function () use ($userData, $profileData, $roles) {
            $user = User::create([
                'name' => $userData['name'],
                'email' => $userData['email'],
                'username' => $userData['username'] ?? str($userData['email'])->before('@')->slug()->toString(),
                'password' => Hash::make($userData['password'] ?? str()->random(12)),
                'setup_required' => $userData['setup_required'] ?? false,
            ]);

            if (!empty($profileData)) {
                $user->profile()->create($profileData);
            }

            if (!empty($roles)) {
                $user->assignRole($roles);
            }

            $this->logAuditAction->execute(
                action: 'user_created',
                subjectType: User::class,
                subjectId: $user->id,
                payload: [
                    'email' => $user->email,
                    'roles' => $roles
                ],
                module: 'Auth'
            );

            return $user;
        });
    }
}
