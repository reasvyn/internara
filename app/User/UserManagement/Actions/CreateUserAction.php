<?php

declare(strict_types=1);

namespace App\User\UserManagement\Actions;

use App\Core\Actions\BaseCommandAction;
use App\User\Models\User;
use App\User\Notifications\WelcomeNotification;
use App\User\Rules\ReservedAuthoritativeName;
use App\User\Rules\SystemUsername;
use App\User\Services\UserIdentifierGenerator;
use App\User\UserManagement\Events\UserCreated;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

final class CreateUserAction extends BaseCommandAction
{
    public function execute(
        array $userData,
        array $profileData = [],
        array $roles = [],
        bool $sendNotification = true,
    ): User {
        $userData['username'] =
            $userData['username'] ??
            UserIdentifierGenerator::generateUsername($userData['email'] ?? '');
        $plainPassword = $userData['password'] ?? str()->random(12);
        $shouldSendWelcome = $sendNotification && ! isset($userData['password']);

        Validator::make($userData, [
            'name' => ['required', 'string', 'max:255', new ReservedAuthoritativeName],
            'username' => [
                'required',
                'string',
                'unique:users,username',
                new SystemUsername,
                new ReservedAuthoritativeName,
            ],
            'email' => ['required', 'email', 'unique:users,email'],
        ])->validate();

        $user = $this->transaction(function () use (
            $userData,
            $profileData,
            $roles,
            $plainPassword,
        ) {
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

            $this->log('user_created', $user, [
                'email' => $user->email,
                'roles' => $roles,
            ]);

            event(new UserCreated($user));

            return $user;
        });

        if ($shouldSendWelcome && $user->email) {
            try {
                $user->notify(new WelcomeNotification($plainPassword));
            } catch (\Throwable) {
                $this->log('welcome_notification_failed', $user, [
                    'user_id' => $user->id,
                    'email' => $user->email,
                ]);
            }
        }

        return $user;
    }
}
