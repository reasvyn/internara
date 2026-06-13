<?php

declare(strict_types=1);

namespace App\Enrollment\Placement\Policies;

use App\Core\Policies\BasePolicy;
use App\Enrollment\Placement\Models\Placement;
use App\User\Models\User;

class PlacementPolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->isAdmin($user);
    }

    public function view(User $user, Placement $placement): bool
    {
        return $this->isAdmin($user);
    }

    public function create(User $user): bool
    {
        return $this->isAdmin($user);
    }

    public function update(User $user, Placement $placement): bool
    {
        return $this->isAdmin($user);
    }

    public function delete(User $user, Placement $placement): bool
    {
        return $this->isAdmin($user) && $placement->registrations()->doesntExist();
    }
}
