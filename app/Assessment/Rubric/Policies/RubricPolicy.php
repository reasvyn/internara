<?php

declare(strict_types=1);

namespace App\Assessment\Rubric\Policies;

use App\Assessment\Rubric\Models\Rubric;
use App\Core\Policies\BasePolicy;
use App\User\Models\User;

class RubricPolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->isAdmin($user);
    }

    public function view(User $user, Rubric $rubric): bool
    {
        return $this->isAdmin($user);
    }

    public function create(User $user): bool
    {
        return $this->isAdmin($user);
    }

    public function update(User $user, Rubric $rubric): bool
    {
        return $this->isAdmin($user);
    }

    public function delete(User $user, Rubric $rubric): bool
    {
        return $this->isAdmin($user);
    }
}
