<?php

declare(strict_types=1);

namespace App\Domain\Policies;

use App\Domain\Assignment\Models\Assignment;
use App\Domain\User\Models\User;

/**
 * S1 - Secure: Only teachers can create/publish assignments.
 */
class AssignmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin', 'teacher', 'supervisor', 'student']);
    }

    public function view(User $user, Assignment $assignment): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin', 'teacher', 'supervisor', 'student']);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin', 'teacher']);
    }

    public function update(User $user, Assignment $assignment): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin', 'teacher']);
    }

    public function publish(User $user, Assignment $assignment): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin', 'teacher']);
    }

    public function delete(User $user, Assignment $assignment): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin']) && ! $assignment->submissions()->exists();
    }
}
