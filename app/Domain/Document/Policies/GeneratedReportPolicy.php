<?php

declare(strict_types=1);

namespace App\Domain\Document\Policies;

use App\Domain\Document\Models\GeneratedReport;
use App\Domain\Shared\Policies\BasePolicy;
use App\Domain\User\Models\User;

/**
 * S1 - Secure: Report access restricted to owner or admin roles.
 */
class GeneratedReportPolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->hasAnyOfRoles($user, [
            'super_admin',
            'admin',
            'teacher',
        ]);
    }

    public function view(User $user, GeneratedReport $report): bool
    {
        return $this->isAdmin($user) || $report->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $this->hasAnyOfRoles($user, [
            'super_admin',
            'admin',
            'teacher',
        ]);
    }

    public function download(User $user, GeneratedReport $report): bool
    {
        return $user->hasRole('super_admin') || $report->user_id === $user->id;
    }

    public function delete(User $user, GeneratedReport $report): bool
    {
        return $user->hasRole('super_admin');
    }
}
