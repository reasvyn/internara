<?php

declare(strict_types=1);

namespace App\Modules\Partners\Domain\Company\Policies;

use App\Modules\Core\Policies\BasePolicy;
use App\Modules\Partners\Domain\Company\Models\Company;
use App\Modules\User\Models\User;

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
        return $this->isAdmin($user) && ! $company->placements()->exists();
    }

    public function forceDelete(User $user, Company $company): bool
    {
        return $user->hasRole('super_admin');
    }
}
