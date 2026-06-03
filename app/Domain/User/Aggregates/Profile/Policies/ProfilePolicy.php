<?php

declare(strict_types=1);

namespace App\Domain\User\Aggregates\Profile\Policies;

use App\Domain\Core\Policies\BasePolicy;
use App\Domain\User\Aggregates\Profile\Models\Profile;
use App\Domain\User\Models\User;

class ProfilePolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->isAdmin($user);
    }

    public function view(User $user, Profile $profile): bool
    {
        return $this->isAdmin($user) || $this->isOwner($user, $profile);
    }

    public function update(User $user, Profile $profile): bool
    {
        return $this->isAdmin($user) || $this->isOwner($user, $profile);
    }
}
