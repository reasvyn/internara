<?php

declare(strict_types=1);

namespace App\SysAdmin\Observability\GdprDeletionLog\Policies;

use App\Core\Policies\BasePolicy;
use App\SysAdmin\Observability\GdprDeletionLog\Models\GdprDeletionLog;
use App\User\Models\User;

class GdprDeletionLogPolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->isAdmin($user);
    }

    public function view(User $user, GdprDeletionLog $log): bool
    {
        return $this->isAdmin($user);
    }

    public function create(User $user): bool
    {
        return $this->isAdmin($user);
    }
}
