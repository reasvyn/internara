<?php

declare(strict_types=1);

namespace App\Guidance\MonitoringVisit\Policies;

use App\Core\Policies\BasePolicy;
use App\Guidance\MonitoringVisit\Models\MonitoringVisit;
use App\User\Models\User;

class MonitoringVisitPolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->hasAnyOfRoles($user, ['super_admin', 'admin', 'teacher']);
    }

    public function view(User $user, MonitoringVisit $visit): bool
    {
        if ($this->isAdmin($user)) {
            return true;
        }

        return $visit->teacher_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $this->hasAnyOfRoles($user, ['super_admin', 'admin', 'teacher']);
    }

    public function update(User $user, MonitoringVisit $visit): bool
    {
        if ($this->isAdmin($user)) {
            return true;
        }

        return $visit->teacher_id === $user->id && $visit->asVisitState()->canBeEdited();
    }

    public function verify(User $user): bool
    {
        return $this->isAdmin($user);
    }

    public function delete(User $user, MonitoringVisit $visit): bool
    {
        if ($this->isAdmin($user)) {
            return true;
        }

        return $visit->teacher_id === $user->id && $visit->asVisitState()->canBeDeleted();
    }
}
