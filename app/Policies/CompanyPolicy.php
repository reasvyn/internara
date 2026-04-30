<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\InternshipCompany;
use App\Models\User;

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

    public function view(?User $user, InternshipCompany $company): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin']);
    }

    public function update(User $user, InternshipCompany $company): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin']);
    }

    public function delete(User $user, InternshipCompany $company): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin'])
            && !$company->placements()->exists();
    }

    public function forceDelete(User $user, InternshipCompany $company): bool
    {
        return $user->hasRole('super_admin');
    }
}
