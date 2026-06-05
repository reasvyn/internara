<?php

declare(strict_types=1);

namespace App\Setup\Policies;

use App\Core\Policies\BasePolicy;
use App\Setup\Models\Setup;
use App\User\Models\User;

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
