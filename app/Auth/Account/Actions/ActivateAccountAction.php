<?php

declare(strict_types=1);

namespace App\Auth\Account\Actions;

use App\Auth\AccessTokens\Models\AccessToken;
use App\Core\Actions\BaseCommandAction;
use App\Core\Exceptions\RejectedException;
use App\User\Models\User;
use Illuminate\Support\Facades\Hash;

final class ActivateAccountAction extends BaseCommandAction
{
    public function execute(User $user, string $code, string $password): User
    {
        return $this->transaction(function () use ($user, $code, $password) {
            if (!AccessToken::verify($user, 'activation', $code)) {
                throw new RejectedException(__('auth.activate.invalid_code'));
            }

            AccessToken::revokeFor($user, 'activation');

            $user->update([
                'password' => Hash::make($password),
            ]);

            $this->log('account_activated', $user);

            return $user;
        });
    }
}
