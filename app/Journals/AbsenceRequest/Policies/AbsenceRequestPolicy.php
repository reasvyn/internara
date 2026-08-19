<?php

declare(strict_types=1);

namespace App\Journals\AbsenceRequest\Policies;

use App\Core\Policies\BasePolicy;
use App\Journals\AbsenceRequest\Models\AbsenceRequest;
use App\User\Models\User;
use App\User\Policies\Concerns\HasMentorProxy;

class AbsenceRequestPolicy extends BasePolicy
{
    use HasMentorProxy;

    public function viewAny(User $user): bool
    {
        return $this->hasAnyOfRoles($user, [
            'super_admin',
            'admin',
            'teacher',
            'supervisor',
            'student',
        ]);
    }

    public function view(User $user, AbsenceRequest $absence): bool
    {
        if ($this->isAdmin($user)) {
            return true;
        }

        if ($absence->user_id === $user->id) {
            return true;
        }

        return $this->mentorProxyFor($absence->registration, $user)?->canVerifyAttendance($user) ?? false;
    }

    public function create(User $user): bool
    {
        return $this->isStudent($user);
    }

    public function update(User $user, AbsenceRequest $absence): bool
    {
        return $this->isAdmin($user);
    }

    public function delete(User $user, AbsenceRequest $absence): bool
    {
        return $this->isAdmin($user);
    }
}
