<?php

declare(strict_types=1);

namespace App\Domain\Settings\Policies;

use App\Domain\Core\Policies\BasePolicy;
use App\Domain\Settings\Models\Setting;
use App\Domain\User\Models\User;

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

    public function update(User $user, Setting $setting): bool
    {
        return $user->hasRole('super_admin');
    }

    public function delete(User $user, Setting $setting): bool
    {
        return $user->hasRole('super_admin');
    }
}
