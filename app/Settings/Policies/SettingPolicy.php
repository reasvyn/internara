<?php

declare(strict_types=1);

namespace App\Settings\Policies;

use App\Core\Policies\BasePolicy;
use App\Settings\Models\Setting;
use App\User\Models\User;

class SettingPolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->isAdmin($user);
    }

    public function view(User $user, Setting $setting): bool
    {
        return $this->isAdmin($user);
    }

    public function create(User $user): bool
    {
        return $user->hasRole('super_admin');
    }

    public function update(User $user): bool
    {
        return $this->isAdmin($user);
    }

    public function delete(User $user): bool
    {
        return $user->hasRole('super_admin');
    }
}
