<?php

declare(strict_types=1);

namespace App\Modules\Auth\Domain\Account\Actions;

use App\Modules\Auth\Domain\AccessTokens\Models\AccessToken;
use App\Modules\Auth\Domain\Account\Data\ActivateAccountData;
use App\Modules\Core\Actions\BaseCommandAction;
use App\Modules\Core\Exceptions\RejectedException;
use App\Modules\User\Models\User;
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
