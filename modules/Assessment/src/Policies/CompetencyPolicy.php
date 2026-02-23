<?php

declare(strict_types=1);

namespace Modules\Assessment\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\User\Models\User;

class CompetencyPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasAnyPermission(['assessment.view', 'assessment.manage']);
    }

    public function view(User $user): bool
    {
        return $user->hasAnyPermission(['assessment.view', 'assessment.manage']);
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('assessment.manage');
    }

    public function update(User $user): bool
    {
        return $user->hasPermissionTo('assessment.manage');
    }

    public function delete(User $user): bool
    {
        return $user->hasPermissionTo('assessment.manage');
    }
}
