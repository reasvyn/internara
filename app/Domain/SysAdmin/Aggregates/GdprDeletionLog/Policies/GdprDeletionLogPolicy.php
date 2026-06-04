<?php

declare(strict_types=1);

namespace App\Domain\SysAdmin\Aggregates\GdprDeletionLog\Policies;

use App\Domain\Core\Policies\BasePolicy;
use App\Domain\SysAdmin\Aggregates\GdprDeletionLog\Models\GdprDeletionLog;
use App\Domain\User\Models\User;

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
