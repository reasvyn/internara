<?php

declare(strict_types=1);

namespace App\Auth\Permissions\Policies;

use App\Core\Policies\BasePolicy;
use App\User\Models\User;

class UserPolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin']);
    }

    public function viewAdmin(User $user): bool
    {
        return $user->hasRole('super_admin');
    }

    public function view(User $user, User $model): bool
    {
        if ($user->id === $model->id) {
            return true;
        }

        return $user->hasAnyRole(['super_admin', 'admin']);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin']);
    }

    public function update(User $user, User $model): bool
    {
        if ($model->hasRole('super_admin')) {
            return $user->id === $model->id;
        }

        if ($user->id === $model->id) {
            return true;
        }

        return $user->hasAnyRole(['super_admin', 'admin']);
    }

    public function delete(User $user, User $model): bool
    {
        if ($model->hasRole('super_admin')) {
            return false;
        }

        if ($user->id === $model->id) {
            return false;
        }

        return $user->hasAnyRole(['super_admin', 'admin']);
    }

    public function restore(User $user, User $model): bool
    {
        if ($model->hasRole('super_admin')) {
            return false;
        }

        return $user->hasAnyRole(['super_admin', 'admin']);
    }

    public function forceDelete(User $user, User $model): bool
    {
        if ($model->hasRole('super_admin')) {
            return false;
        }

        if ($user->id === $model->id) {
            return false;
        }

        return $user->hasAnyRole(['super_admin', 'admin']);
    }
}
