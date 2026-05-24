<?php

declare(strict_types=1);

namespace App\Domain\Internship\Policies;

use App\Domain\Core\Policies\BasePolicy;
use App\Domain\Internship\Models\InternshipPhase;
use App\Domain\User\Models\User;

class InternshipPhasePolicy extends BasePolicy
{
    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, InternshipPhase $phase): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $this->isAdmin($user);
    }

    public function update(User $user, InternshipPhase $phase): bool
    {
        return $this->isAdmin($user);
    }

    public function delete(User $user, InternshipPhase $phase): bool
    {
        return $user->hasRole('super_admin');
    }
}
