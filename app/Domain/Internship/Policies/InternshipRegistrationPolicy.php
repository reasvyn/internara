<?php

declare(strict_types=1);

namespace App\Domain\Internship\Policies;

use App\Domain\Internship\Models\Registration;
use App\Domain\Shared\Policies\BasePolicy;
use App\Domain\User\Models\User;

/**
 * S1 - Secure: Students can only view/edit their own registrations.
 */
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

        if ($this->isTeacher($user) && $registration->teacher_id === $user->id) {
            return true;
        }

        if ($this->isSupervisor($user) && $registration->mentor_id === $user->id) {
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

        return $registration->student_id === $user->id && $registration->isPending();
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

        return $registration->student_id === $user->id && $registration->isPending();
    }
}
