<?php

declare(strict_types=1);

namespace App\Auth\Account\Actions;

use App\Auth\AccessTokens\Models\AccessToken;
use App\Auth\Account\Data\ActivateAccountData;
use App\Core\Actions\BaseCommandAction;
use App\Core\Exceptions\RejectedException;
use App\User\Models\User;
use Illuminate\Support\Facades\Hash;

final class ActivateAccountAction extends BaseCommandAction
{
    public function execute(ActivateAccountData $data): User
    {
        return $this->transaction(function () use ($data) {
            $user = User::find($data->userId);

            if (! $user) {
                throw new RejectedException(__('auth.activate.invalid_email'));
            }

            if (! AccessToken::verify($user, 'activation', $data->code)) {
                throw new RejectedException(__('auth.activate.invalid_code'));
            }

            AccessToken::revokeFor($user, 'activation');

            $user->update([
                'password' => Hash::make($data->password),
            ]);

            $this->log('account_activated', $user);

            return $user;
        });
    }
}
