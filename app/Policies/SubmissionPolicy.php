<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Submission;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Policy for Submission model.
 *
 * S1 - Secure: Students can only submit/view their own submissions.
 * Supervisors/Admin can verify.
 */
class SubmissionPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return true; // Students can view their own
    }

    public function view(User $user, Submission $submission): bool
    {
        return $user->id === $submission->student_id || $user->can('verify-submissions');
    }

    public function create(User $user): bool
    {
        return $user->hasRole('student');
    }

    public function update(User $user, Submission $submission): bool
    {
        // Students can edit their own draft submissions
        return $user->id === $submission->student_id && $submission->canBeEdited();
    }

    public function delete(User $user, Submission $submission): bool
    {
        return $user->id === $submission->student_id && $submission->canBeEdited();
    }

    public function verify(User $user, Submission $submission): bool
    {
        return $user->can('verify-submissions');
    }
}
