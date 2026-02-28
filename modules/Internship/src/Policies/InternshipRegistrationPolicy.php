<?php

declare(strict_types=1);

namespace Modules\Internship\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Internship\Models\InternshipRegistration;
use Modules\User\Models\User;

class InternshipRegistrationPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasAnyRole(['super-admin', 'admin', 'staff', 'student']);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, InternshipRegistration $registration): bool
    {
        return $user->id === $registration->student_id || $user->hasAnyRole(['super-admin', 'admin', 'staff']);
    }

    /**
     * Determine whether the user can view the registration.
     */
    public function view(User $user, InternshipRegistration $registration): bool
    {
        if (! $user->can('registration.view')) {
            return false;
        }

        // Students can view their own registration
        if ($user->id === $registration->student_id) {
            return true;
        }

        // Supervisors (Teacher/Mentor) assigned to this registration
        if ($user->id === $registration->teacher_id || $user->id === $registration->mentor_id) {
            return true;
        }

        return false;
    }
}
