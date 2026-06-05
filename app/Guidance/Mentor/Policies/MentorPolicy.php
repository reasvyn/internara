<?php

declare(strict_types=1);

namespace App\Guidance\Mentor\Policies;

use App\Core\Policies\BasePolicy;
use App\Guidance\Mentor\Models\Mentor;
use App\User\Models\User;

class MentorPolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->hasAnyOfRoles($user, ['super_admin', 'admin']);
    }

    public function view(User $user, Mentor $mentor): bool
    {
        return $this->isAdmin($user);
    }

    public function create(User $user): bool
    {
        return $this->isAdmin($user);
    }

    public function update(User $user, Mentor $mentor): bool
    {
        return $this->isAdmin($user);
    }

    public function delete(User $user, Mentor $mentor): bool
    {
        return $this->isAdmin($user);
    }
}
