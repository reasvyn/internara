<?php

declare(strict_types=1);

namespace App\Domain\Policies;

use App\Domain\Assignment\Models\Submission;
use App\Domain\User\Models\User;

/**
 * S1 - Secure: Students can only view/submit their own submissions.
 */
class SubmissionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin', 'teacher']);
    }

    public function view(User $user, Submission $submission): bool
    {
        if ($user->hasAnyRole(['super_admin', 'admin'])) {
            return true;
        }

        $assignment = $submission->assignment;

        if ($user->hasRole('teacher') && $assignment && $assignment->created_by === $user->id) {
            return true;
        }

        return $submission->student_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->hasRole('student');
    }

    public function update(User $user, Submission $submission): bool
    {
        return $submission->student_id === $user->id && $submission->status?->value === 'submitted';
    }

    public function verify(User $user, Submission $submission): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin', 'teacher']);
    }

    public function delete(User $user, Submission $submission): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin']);
    }
}
