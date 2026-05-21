<?php

declare(strict_types=1);

namespace App\Domain\Certificate\Policies;

use App\Domain\Certificate\Models\Certificate;
use App\Domain\Core\Policies\BasePolicy;
use App\Domain\User\Models\User;

class CertificatePolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->hasAnyOfRoles($user, [
            'super_admin', 'admin', 'student',
        ]);
    }

    public function view(User $user, Certificate $certificate): bool
    {
        if ($this->isAdmin($user)) {
            return true;
        }

        return $certificate->registration?->mentee?->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $this->isAdmin($user);
    }

    public function update(User $user, Certificate $certificate): bool
    {
        return false;
    }

    public function delete(User $user, Certificate $certificate): bool
    {
        return false;
    }

    public function revoke(User $user, Certificate $certificate): bool
    {
        return $this->isAdmin($user);
    }
}
