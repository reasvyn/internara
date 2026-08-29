<?php

declare(strict_types=1);

namespace App\Modules\SysAdmin\Domain\Backups\Policies;

use App\Modules\Core\Policies\BasePolicy;
use App\Modules\SysAdmin\Domain\Backups\Models\Backup;
use App\Modules\User\Models\User;

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
