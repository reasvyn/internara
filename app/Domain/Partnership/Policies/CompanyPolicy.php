<?php

declare(strict_types=1);

namespace App\Domain\Partnership\Policies;

use App\Domain\Core\Policies\BasePolicy;
use App\Domain\Partnership\Models\Company;
use App\Domain\User\Models\User;

class CompanyPolicy extends BasePolicy
{
    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, Company $company): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $this->isAdmin($user);
    }

    public function update(User $user, Company $company): bool
    {
        return $this->isAdmin($user);
    }

    public function delete(User $user, Company $company): bool
    {
        return $this->isAdmin($user);
    }
}
