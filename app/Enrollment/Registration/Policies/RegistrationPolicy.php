<?php

declare(strict_types=1);

namespace App\Enrollment\Registration\Policies;

use App\Core\Policies\BasePolicy;
use App\Enrollment\Registration\Models\Registration;
use App\User\Models\User;

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
        return $this->isStudent($user);
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
        return $user
            ->mentors()
            ->whereHas('registrations', fn ($q) => $q->where('registration_id', $registration->id))
            ->exists();
    }
}
