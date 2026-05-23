<?php

declare(strict_types=1);

namespace App\Domain\Auth\Policies;

use App\Domain\Core\Policies\BasePolicy;
use App\Domain\User\Models\User;

class UserPolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('users.view');
    }

    public function view(User $user, User $model): bool
    {
        if ($user->id === $model->id) {
            return true;
        }

        return $user->can('users.view');
    }

    public function create(User $user): bool
    {
        return $user->can('users.create');
    }

    public function update(User $user, User $model): bool
    {
        if ($model->hasRole('super_admin')) {
            return $user->id === $model->id;
        }

        if ($user->id === $model->id) {
            return true;
        }

        return $user->can('users.edit');
    }

    public function delete(User $user, User $model): bool
    {
        if ($model->hasRole('super_admin')) {
            return false;
        }

        if ($user->id === $model->id) {
            return false;
        }

        return $user->can('users.delete');
    }

    public function restore(User $user, User $model): bool
    {
        if ($model->hasRole('super_admin')) {
            return false;
        }

        return $user->can('users.edit');
    }

    public function forceDelete(User $user, User $model): bool
    {
        if ($model->hasRole('super_admin')) {
            return false;
        }

        if ($user->id === $model->id) {
            return false;
        }

        return $user->can('users.delete');
    }
}
