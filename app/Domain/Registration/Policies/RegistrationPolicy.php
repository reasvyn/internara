<?php

declare(strict_types=1);

namespace App\Domain\Registration\Policies;

use App\Domain\Core\Policies\BasePolicy;
use App\Domain\Registration\Models\Registration;
use App\Domain\User\Models\User;

class RegistrationPolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->hasAnyOfRoles($user, [
            'super_admin', 'admin', 'teacher', 'supervisor',
        ]);
    }

    public function view(User $user, Registration $registration): bool
    {
        if ($this->isAdmin($user)) {
            return true;
        }

        return $registration->mentee?->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $this->isStudent($user);
    }

    public function update(User $user, Registration $registration): bool
    {
        if ($this->isAdmin($user)) {
            return true;
        }

        return $registration->mentee?->user_id === $user->id;
    }

    public function delete(User $user, Registration $registration): bool
    {
        return $this->isAdmin($user);
    }
}
