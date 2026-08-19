<?php

declare(strict_types=1);

namespace App\User\Observers;

use App\Core\Exceptions\RejectedException;
use App\User\Models\User;

class UserObserver
{
    public function deleting(User $user): void
    {
        if ($user->hasRole('superadmin')) {
            throw new RejectedException(__('user.manager.cannot_delete_super_admin'));
        }
    }
}
