<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Assessment;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Policy for Assessment model.
 *
 * S1 - Secure: Only evaluators can create/update assessments.
 */
class AssessmentPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('manage-assessments');
    }

    public function view(User $user, Assessment $assessment): bool
    {
        return $user->id === $assessment->evaluator_id || $user->can('manage-assessments');
    }

    public function create(User $user): bool
    {
        return $user->can('manage-assessments');
    }

    public function update(User $user, Assessment $assessment): bool
    {
        return $user->id === $assessment->evaluator_id && ! $assessment->isFinalized();
    }

    public function delete(User $user, Assessment $assessment): bool
    {
        return $user->can('manage-assessments') && ! $assessment->isFinalized();
    }

    public function finalize(User $user, Assessment $assessment): bool
    {
        return $user->id === $assessment->evaluator_id;
    }
}
