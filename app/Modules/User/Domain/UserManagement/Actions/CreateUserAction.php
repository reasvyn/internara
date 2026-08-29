<?php

declare(strict_types=1);

namespace App\Modules\User\Domain\UserManagement\Actions;

use App\Modules\Auth\Domain\AccessTokens\Models\AccessToken;
use App\Modules\Core\Actions\BaseCommandAction;
use App\Modules\User\Models\User;
use App\Modules\User\Domain\Notifications\WelcomeNotification;
use App\Modules\User\Rules\ReservedAuthoritativeName;
use App\Modules\User\Rules\SystemUsername;
use App\Modules\User\Services\UserIdentifierGenerator;
use App\Modules\User\Domain\UserManagement\Data\CreateUserData;
use App\Modules\User\Domain\UserManagement\Events\UserCreated;
use App\Modules\User\Domain\UserManagement\Notifications\ActivationCodeNotification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

final class CreateUserAction extends BaseCommandAction
{
    public function execute(CreateUserData $data): User
    {
        $userData = $data->user;
        $userData['username'] =
            $userData['username'] ??
            UserIdentifierGenerator::generateUsername($userData['email'] ?? '');
        $plainPassword = $userData['password'] ?? str()->random(12);
        $shouldSendWelcome = $data->sendNotification && ! isset($userData['password']);

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
            $data,
            $plainPassword,
        ) {
            $user = User::create([
                'name' => $userData['name'],
                'email' => $userData['email'],
                'username' => $userData['username'],
                'password' => Hash::make($plainPassword),
                'setup_required' => $userData['setup_required'] ?? false,
            ]);

            if (! empty($data->profile)) {
                $user->profile()->create($data->profile);
            }

            if (! empty($data->roles)) {
                $user->syncRoles($data->roles);
            }

            $this->log('user_created', $user, [
                'email' => $user->email,
                'roles' => $data->roles,
            ]);

            event(new UserCreated($user));

            return $user;
        });

        if ($user->email) {
            try {
                $token = AccessToken::generateFor($user, 'activation', [
                    'name' => 'Account Activation',
                ]);
                $user->notify(new ActivationCodeNotification($user, $token['plain_text']));
            } catch (\Throwable) {
                $this->log('activation_notification_failed', $user, [
                    'user_id' => $user->id,
                    'email' => $user->email,
                ]);
            }

            if ($shouldSendWelcome) {
                try {
                    $user->notify(new WelcomeNotification($plainPassword));
                } catch (\Throwable) {
                    $this->log('welcome_notification_failed', $user, [
                        'user_id' => $user->id,
                        'email' => $user->email,
                    ]);
                }
            }
        }

        return $user;
    }
}
