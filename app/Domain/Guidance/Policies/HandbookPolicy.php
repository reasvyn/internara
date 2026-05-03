<?php

declare(strict_types=1);

namespace App\Domain\Policies;

use App\Domain\Guidance\Models\Handbook;
use App\Domain\User\Models\User;

/**
 * S1 - Secure: Handbook management restricted to admin roles.
 */
class HandbookPolicy
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
        return $user->hasAnyRole(['super_admin', 'admin']);
    }

    public function update(User $user, Handbook $handbook): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin']);
    }

    public function delete(User $user, Handbook $handbook): bool
    {
        return $user->hasRole('super_admin');
    }
}
