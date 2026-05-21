<?php

declare(strict_types=1);

namespace App\Domain\Setup\Policies;

use App\Domain\Core\Policies\BasePolicy;
use App\Domain\Setup\Models\Setup;
use App\Domain\User\Models\User;

class SetupPolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->isAdmin($user);
    }

    public function view(User $user, Setup $setup): bool
    {
        return $this->isAdmin($user);
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Setup $setup): bool
    {
        return $this->isAdmin($user);
    }

    public function delete(User $user, Setup $setup): bool
    {
        return false;
    }
}
