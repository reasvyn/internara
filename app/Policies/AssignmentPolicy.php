<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Assignment;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Policy for Assignment model.
 *
 * S1 - Secure: Authorization checks for CRUD operations.
 */
class AssignmentPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('manage-assignments');
    }

    public function view(User $user, Assignment $assignment): bool
    {
        return $user->can('manage-assignments');
    }

    public function create(User $user): bool
    {
        return $user->can('manage-assignments');
    }

    public function update(User $user, Assignment $assignment): bool
    {
        return $user->can('manage-assignments');
    }

    public function delete(User $user, Assignment $assignment): bool
    {
        return $user->can('manage-assignments');
    }

    public function publish(User $user, Assignment $assignment): bool
    {
        return $user->can('manage-assignments');
    }
}
