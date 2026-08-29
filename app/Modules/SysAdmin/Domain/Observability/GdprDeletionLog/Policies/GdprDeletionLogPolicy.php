<?php

declare(strict_types=1);

namespace App\Modules\SysAdmin\Domain\Observability\GdprDeletionLog\Policies;

use App\Modules\Core\Policies\BasePolicy;
use App\Modules\SysAdmin\Domain\Observability\GdprDeletionLog\Models\GdprDeletionLog;
use App\Modules\User\Models\User;

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
