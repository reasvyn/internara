<?php

declare(strict_types=1);

namespace App\Domain\Internship\Policies;

use App\Domain\Core\Policies\BasePolicy;
use App\Domain\Internship\Models\InternshipGroup;
use App\Domain\User\Models\User;

class InternshipGroupPolicy extends BasePolicy
{
    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, InternshipGroup $group): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $this->isAdmin($user);
    }

    public function update(User $user, InternshipGroup $group): bool
    {
        return $this->isAdmin($user);
    }

    public function delete(User $user, InternshipGroup $group): bool
    {
        return $this->isAdmin($user);
    }
}
