<?php

declare(strict_types=1);

namespace Modules\Internship\Policies;

use Modules\Internship\Models\Internship;
use Modules\Permission\Enums\Permission;
use Modules\Permission\Enums\Role;
use Modules\User\Models\User;

/**
 * Class InternshipPolicy
 *
 * Policy for Internship model operations.
 */
class InternshipPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(?User $user): bool
    {
        if (session('setup_authorized')) {
            return true;
        }

        return $user?->hasAnyPermission([
            Permission::INTERNSHIP_VIEW->value,
            Permission::INTERNSHIP_MANAGE->value,
        ]) ?? false;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(?User $user, Internship $internship): bool
    {
        if (session('setup_authorized')) {
            return true;
        }

        return $user?->hasAnyPermission([
            Permission::INTERNSHIP_VIEW->value,
            Permission::INTERNSHIP_MANAGE->value,
        ]) ?? false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(?User $user): bool
    {
        if (session('setup_authorized')) {
            return true;
        }

        return $user?->hasPermissionTo(Permission::INTERNSHIP_MANAGE->value) ?? false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(?User $user, Internship $internship): bool
    {
        if (session('setup_authorized')) {
            return true;
        }

        return $user?->hasAnyPermission([
            Permission::INTERNSHIP_UPDATE->value,
            Permission::INTERNSHIP_MANAGE->value,
        ]) ?? false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(?User $user, Internship $internship): bool
    {
        if (session('setup_authorized')) {
            return true;
        }

        if (!$user?->hasPermissionTo(Permission::INTERNSHIP_MANAGE->value)) {
            return false;
        }

        return !$internship->registrations()->exists();
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(?User $user, Internship $internship): bool
    {
        return $user?->hasPermissionTo(Permission::INTERNSHIP_MANAGE->value) ?? false;
    }

    /**
     * Determine whether the user can force delete the model.
     */
    public function forceDelete(?User $user, Internship $internship): bool
    {
        return $user?->hasRole(Role::SUPER_ADMIN->value) ?? false;
    }
}