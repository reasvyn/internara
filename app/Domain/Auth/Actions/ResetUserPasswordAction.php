<?php

declare(strict_types=1);

namespace App\Domain\Auth\Actions;

use App\Domain\Auth\Entities\SuperAdminIntegrityRules;
use App\Domain\Core\Actions\BaseAction;
use App\Domain\Core\Exceptions\RejectedException;
use App\Domain\User\Models\User;
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
