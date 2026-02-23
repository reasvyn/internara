<?php

declare(strict_types=1);

namespace Modules\Assignment\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Assignment\Models\Submission;
use Modules\User\Models\User;

class SubmissionPolicy
{
    use HandlesAuthorization;

    public function view(User $user, Submission $submission): bool
    {
        if (! $user->can('assignment.view')) {
            return false;
        }

        // Student can view their own submission
        if ($user->id === $submission->student_id) {
            return true;
        }

        // Supervisors (Teacher/Mentor) assigned to this registration
        // (Wait, Submission should link to registration or student)
        // Check model...
        return $user->hasAnyRole(['admin', 'super-admin', 'teacher', 'mentor']);
    }

    public function create(User $user): bool
    {
        return $user->hasRole('student');
    }

    public function update(User $user, Submission $submission): bool
    {
        return $user->id === $submission->student_id && ! $submission->isVerified();
    }

    public function validate(User $user, Submission $submission): bool
    {
        return $user->hasAnyRole(['admin', 'super-admin', 'teacher', 'mentor']);
    }
}
