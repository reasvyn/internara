<?php

declare(strict_types=1);

namespace App\Partners\Partnership\Policies;

use App\Core\Policies\BasePolicy;
use App\Partners\Partnership\Models\Partnership;
use App\User\Models\User;

class PartnershipPolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->hasAnyOfRoles($user, ['super_admin', 'admin', 'teacher']);
    }

    public function view(User $user, Partnership $partnership): bool
    {
        return $this->isAdmin($user) || $this->isTeacher($user);
    }

    public function create(User $user): bool
    {
        return $this->isAdmin($user);
    }

    public function update(User $user, Partnership $partnership): bool
    {
        return $this->isAdmin($user);
    }

    public function delete(User $user, Partnership $partnership): bool
    {
        return $this->isAdmin($user);
    }
}
