<?php

declare(strict_types=1);

namespace App\User\Observers;

use App\User\Models\User;

class UserObserver
{
    public function deleting(User $user): void
    {
        if ($user->hasRole('superadmin')) {
            throw new \RuntimeException('Super administrator accounts cannot be deleted.');
        }
    }
}
