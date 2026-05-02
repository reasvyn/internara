<?php

declare(strict_types=1);

namespace Modules\Report\Policies;

use Modules\Permission\Enums\Permission;
use Modules\Permission\Enums\Role;
use Modules\Report\Models\GeneratedReport;
use Modules\User\Models\User;

/**
 * Class ReportPolicy
 *
 * Controls access to GeneratedReport model operations.
 */
class ReportPolicy
{
    /**
     * Determine whether the user can view any reports.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo(Permission::REPORT_VIEW->value);
    }

    /**
     * Determine whether the user can view the report.
     */
    public function view(User $user, GeneratedReport $report): bool
    {
        if (! $user->hasPermissionTo(Permission::REPORT_VIEW->value)) {
            return false;
        }

        if ($user->id === $report->generated_by) {
            return true;
        }

        return $user->hasAnyPermission([
            Permission::REPORT_GENERATE->value,
            Permission::REPORT_EXPORT->value,
        ]);
    }

    /**
     * Determine whether the user can create/generate reports.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo(Permission::REPORT_GENERATE->value);
    }

    /**
     * Determine whether the user can export reports.
     */
    public function export(User $user, GeneratedReport $report): bool
    {
        if (! $user->hasPermissionTo(Permission::REPORT_EXPORT->value)) {
            return false;
        }

        return $user->id === $report->generated_by || $user->hasPermissionTo(Permission::REPORT_GENERATE->value);
    }

    /**
     * Determine whether the user can delete reports.
     */
    public function delete(User $user, GeneratedReport $report): bool
    {
        return $user->hasRole(Role::SUPER_ADMIN->value);
    }
}
