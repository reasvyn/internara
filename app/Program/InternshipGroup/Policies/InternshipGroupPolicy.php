<?php

declare(strict_types=1);

namespace App\Program\InternshipGroup\Policies;

use App\Core\Policies\BasePolicy;
use App\Program\InternshipGroup\Models\InternshipGroup;
use App\User\Models\User;

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
