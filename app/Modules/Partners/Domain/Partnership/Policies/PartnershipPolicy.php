<?php

declare(strict_types=1);

namespace App\Modules\Partners\Domain\Partnership\Policies;

use App\Modules\Core\Policies\BasePolicy;
use App\Modules\Partners\Domain\Partnership\Models\Partnership;
use App\Modules\User\Models\User;

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
