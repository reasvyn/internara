<?php

declare(strict_types=1);

namespace Modules\Assessment\Policies;

use Modules\Assessment\Models\Competency;
use Modules\Permission\Enums\Permission;
use Modules\Permission\Enums\Role;
use Modules\User\Models\User;

/**
 * Class CompetencyPolicy
 *
 * Policy for Competency model operations.
 */
class CompetencyPolicy
{
    /**
     * Determine whether the user can view any competencies.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo(Permission::ASSESSMENT_VIEW->value);
    }

    /**
     * Determine whether the user can view the competency.
     */
    public function view(User $user, Competency $competency): bool
    {
        return $user->hasPermissionTo(Permission::ASSESSMENT_VIEW->value);
    }

    /**
     * Determine whether the user can create competencies.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo(Permission::ASSESSMENT_MANAGE->value);
    }

    /**
     * Determine whether the user can update competencies.
     */
    public function update(User $user, Competency $competency): bool
    {
        return $user->hasPermissionTo(Permission::ASSESSMENT_MANAGE->value);
    }

    /**
     * Determine whether the user can delete competencies.
     */
    public function delete(User $user, Competency $competency): bool
    {
        return $user->hasPermissionTo(Permission::ASSESSMENT_MANAGE->value);
    }

    /**
     * Determine whether the user can force delete.
     */
    public function forceDelete(User $user, Competency $competency): bool
    {
        return $user->hasRole(Role::SUPER_ADMIN->value);
    }
}