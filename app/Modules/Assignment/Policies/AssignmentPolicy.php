<?php

declare(strict_types=1);

namespace App\Modules\Assignment\Policies;

use App\Modules\Assignment\Models\Assignment;
use App\Modules\Core\Policies\BasePolicy;
use App\Modules\User\Models\User;

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
        return $this->hasAnyOfRoles($user, ['super_admin', 'admin', 'teacher']);
    }

    public function update(User $user, Assignment $assignment): bool
    {
        return $this->hasAnyOfRoles($user, ['super_admin', 'admin', 'teacher']);
    }

    public function publish(User $user, Assignment $assignment): bool
    {
        return $this->hasAnyOfRoles($user, ['super_admin', 'admin', 'teacher']);
    }

    public function delete(User $user, Assignment $assignment): bool
    {
        return $this->isAdmin($user) && ! $assignment->submissions()->exists();
    }
}
