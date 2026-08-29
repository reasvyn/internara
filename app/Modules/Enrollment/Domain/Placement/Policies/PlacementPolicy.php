<?php

declare(strict_types=1);

namespace App\Modules\Enrollment\Domain\Placement\Policies;

use App\Modules\Core\Policies\BasePolicy;
use App\Modules\Enrollment\Domain\Placement\Models\Placement;
use App\Modules\User\Models\User;

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
