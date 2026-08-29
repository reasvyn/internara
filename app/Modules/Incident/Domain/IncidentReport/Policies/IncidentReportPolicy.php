<?php

declare(strict_types=1);

namespace App\Modules\Incident\Domain\IncidentReport\Policies;

use App\Modules\Core\Policies\BasePolicy;
use App\Modules\Incident\Domain\IncidentReport\Models\IncidentReport;
use App\Modules\User\Models\User;

class IncidentReportPolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->hasAnyOfRoles($user, ['super_admin', 'admin', 'teacher', 'supervisor']);
    }

    public function view(User $user, IncidentReport $report): bool
    {
        if ($this->isAdmin($user)) {
            return true;
        }

        return $report->reported_by === $user->id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, IncidentReport $report): bool
    {
        return $this->isAdmin($user);
    }

    public function delete(User $user, IncidentReport $report): bool
    {
        return $this->isAdmin($user);
    }
}
