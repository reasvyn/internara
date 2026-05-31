<?php

declare(strict_types=1);

namespace App\Domain\Placement\Policies;

use App\Domain\Core\Policies\BasePolicy;
use App\Domain\Placement\Models\Placement;
use App\Domain\User\Models\User;

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
        return $this->isAdmin($user) && $placement->directPlacements()->doesntExist();
    }
}
