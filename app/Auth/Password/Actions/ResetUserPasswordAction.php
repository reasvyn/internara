<?php

declare(strict_types=1);

namespace App\Auth\Password\Actions;

use App\Core\Actions\BaseCommandAction;
use App\Core\Exceptions\RejectedException;
use App\User\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ResetUserPasswordAction extends BaseCommandAction
{
    public function execute(User $user): array
    {
        $integrity = $user->asSuperAdminIntegrityRules();

        if ($integrity->isImmutable()) {
            throw new RejectedException(
                'Cannot reset super admin password through this interface. Use recovery flow instead.',
            );
        }

        $newPassword = Str::password(12);

        $user->update(['password' => Hash::make($newPassword)]);

        return ['user' => $user, 'new_password' => $newPassword];
    }
}
