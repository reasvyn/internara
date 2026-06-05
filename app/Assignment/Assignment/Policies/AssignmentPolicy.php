<?php

declare(strict_types=1);

namespace App\Assignment\Assignment\Policies;

use App\Assignment\Assignment\Models\Assignment;
use App\Core\Policies\BasePolicy;
use App\User\Models\User;

/**
 * S1 - Secure: Only teachers can create/publish assignments.
 */
class AssignmentPolicy extends BasePolicy
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

    public function view(User $user, Assignment $assignment): bool
    {
        return $this->hasAnyOfRoles($user, [
            'super_admin',
            'admin',
            'teacher',
            'supervisor',
            'student',
        ]);
    }

    public function create(User $user): bool
    {
        return $this->hasAnyOfRoles($user, [
            'super_admin',
            'admin',
            'teacher',
        ]);
    }

    public function update(User $user, Assignment $assignment): bool
    {
        return $this->hasAnyOfRoles($user, [
            'super_admin',
            'admin',
            'teacher',
        ]);
    }

    public function publish(User $user, Assignment $assignment): bool
    {
        return $this->hasAnyOfRoles($user, [
            'super_admin',
            'admin',
            'teacher',
        ]);
    }

    public function delete(User $user, Assignment $assignment): bool
    {
        return $this->isAdmin($user) && ! $assignment->submissions()->exists();
    }
}
