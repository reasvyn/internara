<?php

declare(strict_types=1);

namespace App\Auth\Account\Actions;

use App\Core\Actions\BaseAction;
use App\User\Models\User;
use Illuminate\Support\Facades\Hash;

final class ActivateAccountAction extends BaseAction
{
    public function execute(User $user, string $password): User
    {
        return $this->transaction(function () use ($user, $password) {
            $user->update([
                'password' => Hash::make($password),
            ]);

            $this->log('account_activated', $user);

            return $user;
        });
    }
}
