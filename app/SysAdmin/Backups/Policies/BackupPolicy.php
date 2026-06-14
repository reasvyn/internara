<?php

declare(strict_types=1);

namespace App\SysAdmin\Backups\Policies;

use App\Core\Policies\BasePolicy;
use App\SysAdmin\Backups\Models\Backup;
use App\User\Models\User;

class BackupPolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->isAdmin($user);
    }

    public function view(User $user, Backup $backup): bool
    {
        return $this->isAdmin($user);
    }

    public function create(User $user): bool
    {
        return $this->isAdmin($user);
    }

    public function delete(User $user, Backup $backup): bool
    {
        return $this->isAdmin($user);
    }
}
