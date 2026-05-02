<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Assessment;
use App\Models\User;

/**
 * S1 - Secure: Only teachers can create/update assessments. Students can only view their own.
 */
class AssessmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin', 'teacher']);
    }

    public function view(User $user, Assessment $assessment): bool
    {
        if ($user->hasAnyRole(['super_admin', 'admin'])) {
            return true;
        }

        if ($assessment->evaluator_id === $user->id) {
            return true;
        }

        $registration = $assessment->registration;

        return $registration && $registration->student_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin', 'teacher']);
    }

    public function update(User $user, Assessment $assessment): bool
    {
        if ($user->hasAnyRole(['super_admin', 'admin'])) {
            return true;
        }

        return $assessment->evaluator_id === $user->id && ! $assessment->isFinalized();
    }

    public function finalize(User $user, Assessment $assessment): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin', 'teacher']);
    }

    public function delete(User $user, Assessment $assessment): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin']) && ! $assessment->isFinalized();
    }
}
