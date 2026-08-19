<?php

declare(strict_types=1);

namespace App\User\UserManagement\Actions;

use App\Core\Actions\BaseCommandAction;
use App\Core\Exceptions\RejectedException;
use App\User\Models\User;
use App\User\UserManagement\Events\UserDeleted;
use Illuminate\Support\Facades\Auth;

final class DeleteUserAction extends BaseCommandAction
{
    public function execute(User $user): void
    {
        if ($user->hasRole('super_admin')) {
            throw new RejectedException(__('user.manager.cannot_delete_super_admin'));
        }

        if (Auth::id() === $user->id) {
            throw new RejectedException(__('user.manager.cannot_delete_self'));
        }

        $this->transaction(function () use ($user) {
            $this->log('user_deleted', $user, [
                'name' => $user->name,
                'email' => $user->email,
            ]);

            event(new UserDeleted($user));

            $user->delete();
        });
    }
}
