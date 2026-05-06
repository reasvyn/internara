<?php

declare(strict_types=1);

namespace App\Domain\Assessment\Policies;

use App\Domain\Assessment\Models\Assessment;
use App\Domain\Shared\Policies\BasePolicy;
use App\Domain\User\Models\User;

/**
 * S1 - Secure: Only teachers can create/update assessments. Students can only view their own.
 */
class AssessmentPolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->hasAnyOfRoles($user, [
            'super_admin',
            'admin',
            'teacher',
        ]);
    }

    public function view(User $user, Assessment $assessment): bool
    {
        if ($this->isAdmin($user)) {
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
        return $this->hasAnyOfRoles($user, [
            'super_admin',
            'admin',
            'teacher',
        ]);
    }

    public function update(User $user, Assessment $assessment): bool
    {
        if ($this->isAdmin($user)) {
            return true;
        }

        return $assessment->evaluator_id === $user->id && ! $assessment->isFinalized();
    }

    public function finalize(User $user, Assessment $assessment): bool
    {
        return $this->hasAnyOfRoles($user, [
            'super_admin',
            'admin',
            'teacher',
        ]);
    }

    public function delete(User $user, Assessment $assessment): bool
    {
        return $this->isAdmin($user) && ! $assessment->isFinalized();
    }
}
