<?php

declare(strict_types=1);

namespace App\Modules\Academics\Domain\AcademicYear\Policies;

use App\Modules\Academics\Domain\AcademicYear\Models\AcademicYear;
use App\Modules\Core\Policies\BasePolicy;
use App\Modules\User\Models\User;

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
        return false;
    }

    public function delete(User $user, AcademicYear $year): bool
    {
        return false;
    }
}
