<?php

declare(strict_types=1);

namespace App\Academics\Department\Policies;

use App\Academics\Department\Models\Department;
use App\Core\Policies\BasePolicy;
use App\User\Models\User;

class DepartmentPolicy extends BasePolicy
{
    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, Department $department): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $this->isAdmin($user);
    }

    public function update(User $user, Department $department): bool
    {
        return $this->isAdmin($user);
    }

    public function delete(User $user, Department $department): bool
    {
        return $this->isAdmin($user) && $department->asDepartmentState()->canBeDeleted();
    }

    public function forceDelete(User $user, Department $department): bool
    {
        return false;
    }
}
