<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\RequirementSubmission;
use App\Models\User;

/**
 * S1 - Secure: Students can only access their own submissions. File access restricted.
 */
class RequirementSubmissionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin', 'teacher', 'mentor']);
    }

    public function view(User $user, RequirementSubmission $submission): bool
    {
        if ($user->hasAnyRole(['super_admin', 'admin'])) {
            return true;
        }

        $registration = $submission->registration;

        if ($user->hasRole('teacher') && $registration->teacher_id === $user->id) {
            return true;
        }

        if ($user->hasRole('mentor') && $registration->mentor_id === $user->id) {
            return true;
        }

        return $registration->student_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->hasRole('student');
    }

    public function update(User $user, RequirementSubmission $submission): bool
    {
        if ($user->hasAnyRole(['super_admin', 'admin'])) {
            return true;
        }

        return $submission->registration->student_id === $user->id
            && $submission->latestStatus()?->name === 'pending';
    }

    public function verify(User $user, RequirementSubmission $submission): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin', 'teacher']);
    }

    public function delete(User $user, RequirementSubmission $submission): bool
    {
        if ($user->hasAnyRole(['super_admin', 'admin'])) {
            return true;
        }

        return $submission->registration->student_id === $user->id
            && $submission->latestStatus()?->name === 'pending';
    }
}
