<?php

declare(strict_types=1);

namespace App\Actions\User;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ResetUserPasswordAction
{
    /**
     * Reset a user's password to a random string.
     *
     * @return array{user: User, new_password: string}
     */
    public function execute(User $user): array
    {
        $newPassword = Str::random(10);

        $user->update(['password' => Hash::make($newPassword)]);

        return ['user' => $user, 'new_password' => $newPassword];
    }
}
