<?php

declare(strict_types=1);

namespace App\Domain\User\Actions;

use App\Domain\Auth\Notifications\WelcomeNotification;
use App\Domain\Core\Actions\LogAuditAction;
use App\Domain\User\Models\User;
use App\Domain\User\Rules\SystemUsername;
use App\Domain\User\Support\HandlesActionErrors;
use App\Domain\User\Support\UserIdentifierGenerator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use RuntimeException;

/**
 * S1 - Secure: Atomic user creation with profile and role assignment.
 * S2 - Sustain: Proper error handling and logging.
 */
class CreateUserAction
{
    use HandlesActionErrors;

    public function __construct(protected readonly LogAuditAction $logAuditAction) {}

    /**
     * Create a new user with associated profile and roles.
     *
     * @param array{name?: string, email?: string, username?: string, password?: string, setup_required?: bool} $userData
     * @param array<string, mixed> $profileData
     * @param list<string> $roles
     *
     * @throws RuntimeException when user creation fails
     */
    public function execute(array $userData, array $profileData = [], array $roles = []): User
    {
        $userData['username'] =
            $userData['username'] ?? UserIdentifierGenerator::generateUsername();
        $plainPassword = $userData['password'] ?? str()->random(12);
        $shouldSendWelcome = ! isset($userData['password']);

        Validator::make($userData, [
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'unique:users,username', new SystemUsername],
            'email' => ['required', 'email', 'unique:users,email'],
        ])->validate();

        $user = $this->withErrorHandling(function () use ($userData, $profileData, $roles, $plainPassword) {
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
                    $user->syncRoles($roles);
                }

                $this->logAuditAction->execute(
                    action: 'user_created',
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
        }, 'Failed to create user');

        if ($shouldSendWelcome && $user->email) {
            try {
                $user->notify(new WelcomeNotification($plainPassword));
            } catch (\Throwable $e) {
                Log::warning('Failed to send welcome notification', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $user;
    }
}
