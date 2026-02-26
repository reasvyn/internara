<?php

declare(strict_types=1);

namespace Modules\Internship\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\User\Models\User;

class InternshipPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(?User $user): bool
    {
        if ($this->isSetupAuthorized()) {
            return true;
        }

        return $user?->hasAnyPermission(['internship.view', 'internship.manage']) ?? false;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(?User $user, \Modules\Internship\Models\Internship|string|null $internship = null): bool
    {
        if ($this->isSetupAuthorized()) {
            return true;
        }

        return $user?->hasAnyPermission(['internship.view', 'internship.manage']) ?? false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(?User $user, \Modules\Internship\Models\Internship|string|null $internship = null): bool
    {
        if ($this->isSetupAuthorized()) {
            return true;
        }

        return $user?->hasPermissionTo('internship.manage') ?? false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(?User $user, \Modules\Internship\Models\Internship|string|null $internship = null): bool
    {
        if ($this->isSetupAuthorized()) {
            return true;
        }

        return $user?->hasAnyPermission(['internship.update', 'internship.manage']) ?? false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(?User $user, \Modules\Internship\Models\Internship|string|null $internship = null): bool
    {
        if ($this->isSetupAuthorized()) {
            return true;
        }

        return $user?->hasPermissionTo('internship.manage') ?? false;
    }

    /**
     * Check if the current session is an authorized setup session.
     */
    protected function isSetupAuthorized(): bool
    {
        return session(\Modules\Setup\Services\Contracts\SetupService::SESSION_SETUP_AUTHORIZED) === true;
    }
}
