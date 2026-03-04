<?php

declare(strict_types=1);

namespace Modules\Assignment\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Database\Eloquent\Model;
use Modules\User\Models\User;

class AssignmentPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasAnyPermission(['assignment.view', 'assignment.manage']);
    }

    public function view(User $user, Model $model): bool
    {
        return $user->hasAnyPermission(['assignment.view', 'assignment.manage']);
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('assignment.manage');
    }

    public function update(User $user, Model $model): bool
    {
        return $user->hasPermissionTo('assignment.manage');
    }

    public function delete(User $user, Model $model): bool
    {
        return $user->hasPermissionTo('assignment.manage');
    }
}
