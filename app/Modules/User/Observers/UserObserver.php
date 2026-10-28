<?php

declare(strict_types=1);

namespace App\Modules\User\Observers;

use App\Modules\Core\Exceptions\RejectedException;
use App\Modules\User\Models\User;
use Illuminate\Support\Facades\Cache;

class UserObserver
{
    public function created(User $user): void
    {
        $this->invalidate($user);
    }

    public function updated(User $user): void
    {
        $this->invalidate($user);
    }

    public function deleting(User $user): void
    {
        if ($user->hasRole('superadmin')) {
            throw new RejectedException(__('user.manager.cannot_delete_super_admin'));
        }

        $this->invalidate($user);
    }

    private function invalidate(User $user): void
    {
        Cache::forget(config('cache-keys.admin_dashboard_stats'));
        Cache::forget(config('cache-keys.users_count'));
        Cache::forget(config('cache-keys.user_single').$user->id);
    }
}
