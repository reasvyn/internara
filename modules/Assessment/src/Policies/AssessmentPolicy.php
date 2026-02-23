<?php

declare(strict_types=1);

namespace Modules\Assessment\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Assessment\Models\Assessment;
use Modules\User\Models\User;

/**
 * Class AssessmentPolicy
 *
 * Controls access to Assessment model operations.
 */
class AssessmentPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any assessments.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('assessment.view');
    }

    /**
     * Determine whether the user can view the assessment.
     */
    public function view(User $user, Assessment $assessment): bool
    {
        if (! $user->can('assessment.view')) {
            return false;
        }

        // Student can view their own assessment
        if ($user->id === $assessment->registration->student_id) {
            return true;
        }

        // Supervisors (Teacher/Mentor) assigned to this registration
        $registration = $assessment->registration;
        if ($user->id === $registration->teacher_id || $user->id === $registration->mentor_id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can create assessments.
     */
    public function create(User $user): bool
    {
        return $user->can('assessment.manage');
    }

    /**
     * Determine whether the user can update the assessment.
     */
    public function update(User $user, Assessment $assessment): bool
    {
        if (! $user->can('assessment.manage')) {
            return false;
        }

        // Only the evaluator can update their own assessment before it's finalized
        // Note: submitEvaluation currently automatically finalizes it.
        return $user->id === $assessment->evaluator_id && ! $assessment->isFinalized();
    }

    /**
     * Determine whether the user can delete the assessment.
     */
    public function delete(User $user, Assessment $assessment): bool
    {
        return $user->can('assessment.manage') && $user->id === $assessment->evaluator_id;
    }
}
