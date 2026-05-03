<?php

declare(strict_types=1);

namespace App\Domain\Policies;

use App\Domain\School\Models\AcademicYear;
use App\Domain\User\Models\User;

/**
 * S1 - Secure: Academic year management restricted to admin roles.
 */
class AcademicPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, AcademicYear $year): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin']);
    }

    public function update(User $user, AcademicYear $year): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin']);
    }

    public function activate(User $user, AcademicYear $year): bool
    {
        return $user->hasRole('super_admin');
    }

    public function delete(User $user, AcademicYear $year): bool
    {
        return $user->hasRole('super_admin');
    }
}
