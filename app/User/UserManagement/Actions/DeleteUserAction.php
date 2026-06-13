<?php

declare(strict_types=1);

namespace App\User\UserManagement\Actions;

use App\Core\Actions\BaseAction;
use App\Core\Exceptions\RejectedException;
use App\User\Models\User;
use Illuminate\Support\Facades\Auth;

final class DeleteUserAction extends BaseAction
{
    public function execute(User $user): void
    {
        if ($user->hasRole('super_admin')) {
            throw new RejectedException('Super administrator accounts cannot be deleted.');
        }

        if (Auth::id() === $user->id) {
            throw new RejectedException('You cannot delete your own account.');
        }

        $this->transaction(function () use ($user) {
            $this->log('user_deleted', $user, [
                'name' => $user->name,
                'email' => $user->email,
            ]);

            $user->delete();
        });
    }
}
