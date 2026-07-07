<?php

declare(strict_types=1);

namespace App\Auth\Password\Actions;

use App\Core\Actions\BaseCommandAction;
use App\Core\Data\ActionResponse;
use App\Core\Exceptions\RejectedException;
use App\User\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

final class ResetUserPasswordAction extends BaseCommandAction
{
    public function execute(User $user): ActionResponse
    {
        $integrity = $user->asSuperAdminIntegrityRules();

        if ($integrity->isImmutable()) {
            throw new RejectedException(
                'Cannot reset super admin password through this interface. Use recovery flow instead.',
            );
        }

        $newPassword = Str::password(12);

        return ActionResponse::ok(
            $this->transaction(function () use ($user, $newPassword) {
                $user->update(['password' => Hash::make($newPassword)]);

                $this->log('user_password_reset', $user, ['user_id' => $user->id]);

                return ['user' => $user, 'new_password' => $newPassword];
            }),
        );
    }
}
