<?php

declare(strict_types=1);

namespace App\Modules\User\Domain\Profile\Policies;

use App\Modules\Core\Policies\BasePolicy;
use App\Modules\User\Models\User;
use App\Modules\User\Domain\Profile\Models\Profile;

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
