<?php

declare(strict_types=1);

namespace Modules\Assessment\Policies;

use Modules\Assessment\Models\Assessment;
use Modules\Permission\Enums\Permission;
use Modules\Permission\Enums\Role;
use Modules\User\Models\User;

/**
 * Class AssessmentPolicy
 *
 * Controls access to Assessment model operations.
 */
class AssessmentPolicy
{
    /**
     * Determine whether the user can view any assessments.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo(Permission::ASSESSMENT_VIEW->value);
    }

    /**
     * Determine whether the user can view the assessment.
     */
    public function view(User $user, Assessment $assessment): bool
    {
        if (! $user->hasPermissionTo(Permission::ASSESSMENT_VIEW->value)) {
            return false;
        }

        if ($user->id === $assessment->registration->student_id) {
            return true;
        }

        $registration = $assessment->registration;

        return $user->id === $registration->teacher_id || $user->id === $registration->mentor_id;
    }

    /**
     * Determine whether the user can create assessments.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo(Permission::ASSESSMENT_MANAGE->value);
    }

    /**
     * Determine whether the user can update the assessment.
     */
    public function update(User $user, Assessment $assessment): bool
    {
        if (! $user->hasPermissionTo(Permission::ASSESSMENT_MANAGE->value)) {
            return false;
        }

        return $user->id === $assessment->evaluator_id && ! $assessment->isFinalized();
    }

    /**
     * Determine whether the user can delete the assessment.
     */
    public function delete(User $user, Assessment $assessment): bool
    {
        return $user->hasPermissionTo(Permission::ASSESSMENT_MANAGE->value)
            && $user->id === $assessment->evaluator_id;
    }

    /**
     * Determine whether the user can grade the assessment.
     */
    public function grade(User $user, Assessment $assessment): bool
    {
        return $user->hasPermissionTo(Permission::ASSESSMENT_GRADE->value)
            && $user->id === $assessment->evaluator_id;
    }

    /**
     * Determine whether the user can force delete the assessment.
     */
    public function forceDelete(User $user, Assessment $assessment): bool
    {
        return $user->hasRole(Role::SUPER_ADMIN->value);
    }
}
