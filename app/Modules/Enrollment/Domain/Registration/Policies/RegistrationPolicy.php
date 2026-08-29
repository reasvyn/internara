<?php

declare(strict_types=1);

namespace App\Modules\Enrollment\Domain\Registration\Policies;

use App\Modules\Core\Policies\BasePolicy;
use App\Modules\Enrollment\Domain\Registration\Models\Registration;
use App\Modules\User\Models\User;

class RegistrationPolicy extends BasePolicy
{
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

    public function view(User $user, Registration $registration): bool
    {
        if ($this->isAdmin($user)) {
            return true;
        }

        if ($this->isAssignedMentor($user, $registration)) {
            return true;
        }

        return $registration->student_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $this->hasAnyOfRoles($user, ['super_admin', 'admin', 'student']);
    }

    public function update(User $user, Registration $registration): bool
    {
        if ($this->isAdmin($user)) {
            return true;
        }

        return $this->isOwner($registration, $user) && $registration->isPending();
    }

    public function approve(User $user, Registration $registration): bool
    {
        return $this->isAdmin($user);
    }

    public function delete(User $user, Registration $registration): bool
    {
        if ($this->isAdmin($user)) {
            return true;
        }

        return $this->isOwner($registration, $user) && $registration->isPending();
    }

    private function isAssignedMentor(User $user, Registration $registration): bool
    {
        return $registration
            ->mentors()
            ->where('user_id', $user->id)
            ->exists();
    }
}
