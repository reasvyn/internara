<?php

declare(strict_types=1);

namespace App\User\Password\Actions;

use App\Core\Actions\BaseAction;
use App\Exceptions\RejectedException;
use App\User\Models\User;
use App\User\SuperAdmin\Entities\SuperAdminIntegrityRules;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ResetUserPasswordAction extends BaseAction
{
    public function execute(User $user): array
    {
        $integrity = SuperAdminIntegrityRules::fromModel($user);

        if ($integrity->isImmutable()) {
            throw new RejectedException('Cannot reset super admin password through this interface. Use recovery flow instead.');
        }

        $newPassword = Str::password(12);

        $user->update(['password' => Hash::make($newPassword)]);

        return ['user' => $user, 'new_password' => $newPassword];
    }
}
