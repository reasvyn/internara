<?php

declare(strict_types=1);

namespace App\Domain\Policies;

use App\Domain\Internship\Models\Company;
use App\Domain\User\Models\User;

/**
 * S1 - Secure: Company deletion blocked if placements exist.
 * S2 - Sustain: Clear authorization rules for industry partners.
 */
class CompanyPolicy
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
        return $user->hasAnyRole(['super_admin', 'admin']);
    }

    public function update(User $user, Company $company): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin']);
    }

    public function delete(User $user, Company $company): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin']) && ! $company->placements()->exists();
    }

    public function forceDelete(User $user, Company $company): bool
    {
        return $user->hasRole('super_admin');
    }
}
