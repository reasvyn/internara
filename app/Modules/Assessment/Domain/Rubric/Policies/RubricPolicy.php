<?php

declare(strict_types=1);

namespace App\Modules\Assessment\Domain\Rubric\Policies;

use App\Modules\Assessment\Domain\Rubric\Models\Rubric;
use App\Modules\Core\Policies\BasePolicy;
use App\Modules\User\Models\User;

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
