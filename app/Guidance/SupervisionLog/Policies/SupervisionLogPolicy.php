<?php

declare(strict_types=1);

namespace App\Guidance\SupervisionLog\Policies;

use App\Core\Policies\BasePolicy;
use App\Guidance\SupervisionLog\Models\SupervisionLog;
use App\User\Models\User;
use App\User\Policies\Concerns\HasMentorProxy;

class SupervisionLogPolicy extends BasePolicy
{
    use HasMentorProxy;
    public function viewAny(User $user): bool
    {
        return $this->hasAnyOfRoles($user, ['super_admin', 'admin', 'supervisor', 'student']);
    }

    public function view(User $user, SupervisionLog $log): bool
    {
        if ($this->isAdmin($user)) {
            return true;
        }

        $registration = $log->registration;

        if ($registration && $registration->student_id === $user->id) {
            return true;
        }

        if ($log->supervisor_id === $user->id) {
            return true;
        }

        return $this->mentorProxyFor($registration, $user)?->canReviewSupervisionLog($user) ?? false;
    }

    public function create(User $user): bool
    {
        return $user->hasRole('student');
    }

    public function update(User $user, SupervisionLog $log): bool
    {
        $registration = $log->registration;

        return $registration && $registration->student_id === $user->id
            && $log->asSupervisionLogState()->canBeEdited();
    }

    public function review(User $user, SupervisionLog $log): bool
    {
        if ($log->supervisor_id === $user->id && $user->hasRole('supervisor')) {
            return true;
        }

        return $this->mentorProxyFor($log->registration, $user)?->canReviewSupervisionLog($user) ?? false;
    }

    public function delete(User $user, SupervisionLog $log): bool
    {
        if ($this->isAdmin($user)) {
            return true;
        }

        return $log->registration?->student_id === $user->id
            && $log->asSupervisionLogState()->canBeEdited();
    }
}
