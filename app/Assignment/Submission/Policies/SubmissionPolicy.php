<?php

declare(strict_types=1);

namespace App\Assignment\Submission\Policies;

use App\Assignment\Submission\Models\Submission;
use App\Core\Policies\BasePolicy;
use App\User\Models\User;
use App\User\Policies\Concerns\HasMentorProxy;

class SubmissionPolicy extends BasePolicy
{
    use HasMentorProxy;
    public function viewAny(User $user): bool
    {
        return $this->hasAnyOfRoles($user, ['super_admin', 'admin', 'teacher']);
    }

    public function view(User $user, Submission $submission): bool
    {
        if ($this->isAdmin($user)) {
            return true;
        }

        $assignment = $submission->assignment;

        if ($this->isTeacher($user) && $assignment && $assignment->created_by === $user->id) {
            return true;
        }

        return $submission->student_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $this->isStudent($user);
    }

    public function update(User $user, Submission $submission): bool
    {
        return $submission->student_id === $user->id && $submission->status?->value === 'submitted';
    }

    public function verify(User $user, Submission $submission): bool
    {
        if ($this->isAdmin($user)) {
            return true;
        }

        return $this->mentorProxyFor($submission->registration, $user)?->canGradeSubmission($user) ?? false;
    }

    public function delete(User $user, Submission $submission): bool
    {
        return $this->isAdmin($user);
    }
}
