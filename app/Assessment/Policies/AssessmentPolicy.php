<?php

declare(strict_types=1);

namespace App\Assessment\Policies;

use App\Assessment\Models\Assessment;
use App\Core\Policies\BasePolicy;
use App\User\Models\User;

class AssessmentPolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->hasAnyOfRoles($user, ['super_admin', 'admin', 'teacher']);
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
        return $this->hasAnyOfRoles($user, ['super_admin', 'admin', 'teacher']);
    }

    public function update(User $user, Assessment $assessment): bool
    {
        if ($this->isAdmin($user)) {
            return true;
        }

        if ($assessment->evaluator_id === $user->id && ! $assessment->asAssessmentResult()->isFinalized()) {
            return true;
        }

        $registration = $assessment->registration;

        if ($registration && $this->mentorProxyFor($registration, $user)?->canScoreCompetency($user, 'supervisor')) {
            return true;
        }

        return false;
    }

    public function finalize(User $user, Assessment $assessment): bool
    {
        return $this->hasAnyOfRoles($user, ['super_admin', 'admin', 'teacher']);
    }

    public function delete(User $user, Assessment $assessment): bool
    {
        return $this->isAdmin($user) && ! $assessment->asAssessmentResult()->isFinalized();
    }
}
