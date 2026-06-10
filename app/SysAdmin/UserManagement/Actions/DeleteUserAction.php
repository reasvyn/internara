<?php

declare(strict_types=1);

namespace App\SysAdmin\UserManagement\Actions;

use App\Core\Actions\BaseAction;
use App\User\Models\User;
use Illuminate\Support\Facades\Auth;
use RuntimeException;

final class DeleteUserAction extends BaseAction
{
    public function execute(User $user): void
    {
        if ($user->hasRole('super_admin')) {
            throw new RuntimeException('Super administrator accounts cannot be deleted.');
        }

        if (Auth::id() === $user->id) {
            throw new RuntimeException('You cannot delete your own account.');
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
