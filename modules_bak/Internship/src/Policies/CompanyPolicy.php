<?php

declare(strict_types=1);

namespace Modules\Internship\Policies;

use Modules\Internship\Models\Company;
use Modules\Permission\Enums\Permission;
use Modules\Permission\Enums\Role;
use Modules\User\Models\User;

/**
 * Class CompanyPolicy
 *
 * Policy for Company model operations.
 */
class CompanyPolicy
{
    /**
     * Determine whether the user can view any companies.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasAnyPermission([
            Permission::INTERNSHIP_VIEW->value,
            Permission::INTERNSHIP_MANAGE->value,
        ]);
    }

    /**
     * Determine whether the user can view the company.
     */
    public function view(User $user, Company $company): bool
    {
        return $user->hasAnyPermission([
            Permission::INTERNSHIP_VIEW->value,
            Permission::INTERNSHIP_MANAGE->value,
        ]);
    }

    /**
     * Determine whether the user can create companies.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo(Permission::INTERNSHIP_MANAGE->value);
    }

    /**
     * Determine whether the user can update the company.
     */
    public function update(User $user, Company $company): bool
    {
        return $user->hasAnyPermission([
            Permission::INTERNSHIP_UPDATE->value,
            Permission::INTERNSHIP_MANAGE->value,
        ]);
    }

    /**
     * Determine whether the user can delete the company.
     */
    public function delete(User $user, Company $company): bool
    {
        if (!$user->hasPermissionTo(Permission::INTERNSHIP_MANAGE->value)) {
            return false;
        }

        if (!$company->internshipPlacements()->exists()) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can restore the company.
     */
    public function restore(User $user, Company $company): bool
    {
        return $user->hasPermissionTo(Permission::INTERNSHIP_MANAGE->value);
    }

    /**
     * Determine whether the user can force delete the company.
     */
    public function forceDelete(User $user, Company $company): bool
    {
        return $user->hasRole(Role::SUPER_ADMIN->value);
    }
}