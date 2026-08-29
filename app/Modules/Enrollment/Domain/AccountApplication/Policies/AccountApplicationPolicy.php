<?php

declare(strict_types=1);

namespace App\Modules\Enrollment\Domain\AccountApplication\Policies;

use App\Modules\Core\Policies\BasePolicy;
use App\Modules\Enrollment\Domain\AccountApplication\Models\AccountApplication;
use App\Modules\User\Models\User;

class AccountApplicationPolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->hasAnyOfRoles($user, ['super_admin', 'admin']);
    }

    public function view(User $user, AccountApplication $application): bool
    {
        if ($this->isAdmin($user)) {
            return true;
        }

        return $application->email === $user->email;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, AccountApplication $application): bool
    {
        return $this->isAdmin($user);
    }

    public function delete(User $user, AccountApplication $application): bool
    {
        return $this->isAdmin($user);
    }
}
