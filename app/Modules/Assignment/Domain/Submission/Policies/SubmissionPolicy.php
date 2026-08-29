<?php

declare(strict_types=1);

namespace App\Modules\Assignment\Domain\Submission\Policies;

use App\Modules\Assignment\Domain\Submission\Models\Submission;
use App\Modules\Core\Policies\BasePolicy;
use App\Modules\User\Models\User;
use App\Modules\User\Policies\Concerns\HasMentorProxy;

class SubmissionPolicy extends BasePolicy
{
    use HasMentorProxy;

    public function viewAny(User $user): bool
    {
        return $this->hasAnyOfRoles($user, ['super_admin', 'admin', 'teacher', 'supervisor']);
    }

    public function view(User $user, Submission $submission): bool
    {
        if ($this->isAdmin($user)) {
            return true;
        }

        if ($submission->student_id === $user->id) {
            return true;
        }

        return $this->mentorProxyFor($submission->registration, $user)?->canGradeSubmission($user) ?? false;
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
