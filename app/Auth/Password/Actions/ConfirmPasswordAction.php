<?php

declare(strict_types=1);

namespace App\Auth\Password\Actions;

use App\Core\Actions\BaseCommandAction;
use App\Core\Exceptions\RejectedException;
use App\User\Models\User;
use Illuminate\Support\Facades\Hash;

class ConfirmPasswordAction extends BaseCommandAction
{
    public function execute(User $user, string $password): void
    {
        if (! Hash::check($password, $user->password)) {
            throw new RejectedException(
                __('auth.password_confirmation_failed') ??
                    'The provided password does not match your current password.',
            );
        }

        session(['auth.password_confirmed_at' => time()]);

        $this->log('password_confirmed', $user);
    }
}
