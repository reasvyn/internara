<?php

declare(strict_types=1);

namespace Modules\Report\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Report\Models\GeneratedReport;
use Modules\User\Models\User;

/**
 * Class ReportPolicy
 *
 * Controls access to GeneratedReport model operations.
 */
class ReportPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any reports.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('report.view');
    }

    /**
     * Determine whether the user can view the report.
     */
    public function view(User $user, GeneratedReport $report): bool
    {
        if (! $user->can('report.view')) {
            return false;
        }

        // Only the user who generated the report or an admin can view it
        return $user->id === $report->user_id || $user->hasRole('super-admin');
    }

    /**
     * Determine whether the user can delete the report.
     */
    public function delete(User $user, GeneratedReport $report): bool
    {
        return $user->id === $report->user_id || $user->hasRole('super-admin');
    }
}
