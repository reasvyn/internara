<?php

declare(strict_types=1);

namespace App\SysAdmin\Account\Actions;

use App\Core\Actions\BaseAction;
use App\Core\Support\SmartLogger;
use App\User\Models\User;
use App\User\Notifications\WelcomeNotification;
use App\User\Rules\ReservedAuthoritativeName;
use App\User\Rules\SystemUsername;
use App\User\Support\UserIdentifierGenerator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use RuntimeException;

/**
 * S1 - Secure: Atomic user creation with profile and role assignment.
 * S2 - Sustain: Proper error handling and logging.
 */
final class CreateUserAction extends BaseAction
{
    /**
     * Create a new user with associated profile and roles.
     *
     * @param array{name?: string, email?: string, username?: string, password?: string, setup_required?: bool} $userData
     * @param array<string, mixed> $profileData
     * @param list<string> $roles
     *
     * @throws RuntimeException when user creation fails
     */
    public function execute(array $userData, array $profileData = [], array $roles = [], bool $sendNotification = true): User
    {
        $userData['username'] =
            $userData['username'] ?? UserIdentifierGenerator::generateUsername($userData['email'] ?? '');
        $plainPassword = $userData['password'] ?? str()->random(12);
        $shouldSendWelcome = $sendNotification && ! isset($userData['password']);

        Validator::make($userData, [
            'name' => ['required', 'string', 'max:255', new ReservedAuthoritativeName],
            'username' => ['required', 'string', 'unique:users,username', new SystemUsername, new ReservedAuthoritativeName],
            'email' => ['required', 'email', 'unique:users,email'],
        ])->validate();

        $user = $this->transaction(function () use ($userData, $profileData, $roles, $plainPassword) {
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

            SmartLogger::info('user_created')
                ->event('user_created')
                ->module('Auth')
                ->about($user)
                ->withPayload([
                    'email' => $user->email,
                    'roles' => $roles,
                ])
                ->activityOnly()
                ->save();

            return $user;
        });

        if ($shouldSendWelcome && $user->email) {
            try {
                $user->notify(new WelcomeNotification($plainPassword));
            } catch (\Throwable $e) {
                SmartLogger::warning('Failed to send welcome notification')
                    ->withPayload([
                        'user_id' => $user->id,
                        'email' => $user->email,
                        'error' => $e->getMessage(),
                    ])
                    ->systemOnly()
                    ->save();
            }
        }

        return $user;
    }
}
