<?php

declare(strict_types=1);

namespace App\Guidance\Mentee\Policies;

use App\Core\Policies\BasePolicy;
use App\Guidance\Mentee\Models\Mentee;
use App\User\Models\User;

class MenteePolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->hasAnyOfRoles($user, [
            'super_admin', 'admin', 'teacher', 'supervisor',
        ]);
    }

    public function view(User $user, Mentee $mentee): bool
    {
        if ($this->isAdmin($user)) {
            return true;
        }

        return $mentee->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $this->isAdmin($user);
    }

    public function update(User $user, Mentee $mentee): bool
    {
        return $this->isAdmin($user);
    }

    public function delete(User $user, Mentee $mentee): bool
    {
        return $this->isAdmin($user);
    }
}
