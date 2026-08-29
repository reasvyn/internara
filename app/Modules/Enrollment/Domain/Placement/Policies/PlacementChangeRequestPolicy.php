<?php

declare(strict_types=1);

namespace App\Modules\Enrollment\Domain\Placement\Policies;

use App\Modules\Core\Policies\BasePolicy;
use App\Modules\Enrollment\Domain\Placement\Models\PlacementChangeRequest;
use App\Modules\User\Models\User;

class PlacementChangeRequestPolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->isAdmin($user) || $this->isTeacher($user) || $this->isStudent($user);
    }

    public function view(User $user, PlacementChangeRequest $request): bool
    {
        return $this->isAdmin($user) || $this->isOwner($user, $request, 'requested_by');
    }

    public function create(User $user): bool
    {
        return $this->isStudent($user);
    }

    public function update(User $user, PlacementChangeRequest $request): bool
    {
        return $this->isAdmin($user);
    }

    public function delete(User $user, PlacementChangeRequest $request): bool
    {
        return $this->isAdmin($user);
    }
}
