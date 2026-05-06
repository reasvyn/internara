<?php

declare(strict_types=1);

namespace App\Domain\Guidance\Policies;

use App\Domain\Guidance\Models\Handbook;
use App\Domain\Shared\Policies\BasePolicy;
use App\Domain\User\Models\User;

/**
 * S1 - Secure: Handbook management restricted to admin roles.
 */
class HandbookPolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Handbook $handbook): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $this->isAdmin($user);
    }

    public function update(User $user, Handbook $handbook): bool
    {
        return $this->isAdmin($user);
    }

    public function delete(User $user, Handbook $handbook): bool
    {
        return $user->hasRole('super_admin');
    }
}
