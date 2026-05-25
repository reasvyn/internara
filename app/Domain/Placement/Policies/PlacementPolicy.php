<?php

declare(strict_types=1);

namespace App\Domain\Placement\Policies;

use App\Domain\Core\Policies\BasePolicy;
use App\Domain\User\Models\User;

class PlacementPolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->isAdmin($user);
    }
}
