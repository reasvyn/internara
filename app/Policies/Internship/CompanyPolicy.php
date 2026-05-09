<?php

declare(strict_types=1);

namespace App\Policies\Internship;

use App\Models\Company;
use App\Models\User;
use App\Policies\Shared\BasePolicy;

/**
 * S1 - Secure: Company deletion blocked if placements exist.
 * S2 - Sustain: Clear authorization rules for industry partners.
 */
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
