<?php

declare(strict_types=1);

namespace App\Modules\Auth\Domain\Password\Actions;

use App\Modules\Core\Actions\BaseCommandAction;
use App\Modules\Core\Data\ActionResponse;
use App\Modules\Core\Exceptions\RejectedException;
use App\Modules\User\Models\User;
use Illuminate\Support\Facades\Hash;

final class ConfirmPasswordAction extends BaseCommandAction
{
    public function execute(User $user, string $password): ActionResponse
    {
        if (! Hash::check($password, $user->password)) {
            throw new RejectedException(
                __('auth.password_confirmation_failed') ??
                    'The provided password does not match your current password.',
            );
        }

        session(['auth.password_confirmed_at' => time()]);

        $this->log('password_confirmed', $user);

        return ActionResponse::ok();
    }
}
