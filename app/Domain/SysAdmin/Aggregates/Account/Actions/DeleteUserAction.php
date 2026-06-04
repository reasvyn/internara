<?php

declare(strict_types=1);

namespace App\Domain\SysAdmin\Aggregates\Account\Actions;

use App\Domain\Core\Actions\BaseAction;
use App\Domain\Core\Support\SmartLogger;
use App\Domain\User\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class DeleteUserAction extends BaseAction
{
    public function execute(User $user): void
    {
        if ($user->hasRole('super_admin')) {
            SmartLogger::warning('super_admin_delete_blocked')
                ->event('super_admin.delete_blocked')
                ->module('Auth')
                ->about($user)
                ->systemOnly()
                ->save();

            throw new RuntimeException('Super administrator accounts cannot be deleted.');
        }

        if (Auth::id() === $user->id) {
            throw new RuntimeException('You cannot delete your own account.');
        }

        DB::transaction(function () use ($user) {
            SmartLogger::info('user_deleted')
                ->event('user_deleted')
                ->module('Auth')
                ->about($user)
                ->withPayload([
                    'name' => $user->name,
                    'email' => $user->email,
                ])
                ->activityOnly()
                ->save();

            $user->delete();
        });
    }
}
