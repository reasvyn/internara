<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Actions\Audit\LogAuditAction;
use App\Models\User;
use App\Notifications\WelcomeNotification;
use App\Rules\SystemUsername;
use App\Support\UserIdentifierGenerator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

/**
 * S1 - Secure: Atomic user creation with profile and role assignment.
 */
class CreateUserAction
{
    public function __construct(
        protected readonly LogAuditAction $logAuditAction
    ) {}

    /**
     * Create a new user with associated profile and roles.
     */
    public function execute(array $userData, array $profileData = [], array $roles = []): User
    {
        $userData['username'] = $userData['username'] ?? UserIdentifierGenerator::generateUsername();
        $plainPassword = $userData['password'] ?? str()->random(12);

        Validator::make($userData, [
            'username' => ['required', 'string', 'unique:users,username', new SystemUsername],
            'email' => ['required', 'email', 'unique:users,email'],
        ])->validate();

        return DB::transaction(function () use ($userData, $profileData, $roles, $plainPassword) {
            $user = User::create([
                'name' => $userData['name'],
                'email' => $userData['email'],
                'username' => $userData['username'],
                'password' => Hash::make($plainPassword),
                'setup_required' => $userData['setup_required'] ?? false,
            ]);

            if (! empty($profileData)) {
                $user->profile()->create($profileData);
            }

            if (! empty($roles)) {
                $user->assignRole($roles);
            }

            // Notify User (Welcome)
            $user->notify(new WelcomeNotification(
                isset($userData['password']) ? '' : $plainPassword
            ));

            $this->logAuditAction->execute(
                action: 'user_created',
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
