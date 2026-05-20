<?php

declare(strict_types=1);

namespace App\Domain\Auth\Actions;

use App\Domain\Core\Actions\BaseAction;
use App\Domain\User\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ResetUserPasswordAction extends BaseAction
{
    public function execute(User $user): array
    {
        $newPassword = Str::random(10);

        $user->update(['password' => Hash::make($newPassword)]);

        return ['user' => $user, 'new_password' => $newPassword];
    }
}
