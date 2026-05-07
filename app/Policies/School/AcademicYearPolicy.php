<?php

declare(strict_types=1);

namespace App\Policies\School;

use App\Models\School\AcademicYear;
use App\Models\User;
use App\Policies\Shared\BasePolicy;

/**
 * S1 - Secure: Academic year management restricted to admin roles.
 */
class AcademicYearPolicy extends BasePolicy
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
        return $this->isAdmin($user);
    }

    public function update(User $user, AcademicYear $year): bool
    {
        return $this->isAdmin($user);
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
