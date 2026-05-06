<?php

declare(strict_types=1);

namespace App\Domain\Shared\Policies;

use App\Domain\Shared\Policies\Concerns\AuthorizesOwnership;
use App\Domain\Shared\Policies\Concerns\AuthorizesRoles;

/**
 * Base class for all domain policies.
 *
 * Provides shared role-based and ownership authorization methods to eliminate
 * duplicated hasAnyRole and owner-check patterns across policies.
 *
 * Usage:
 * class CompanyPolicy extends BasePolicy { ... }
 *
 * Or use traits directly for existing policies:
 * class ExistingPolicy { use AuthorizesRoles, AuthorizesOwnership; }
 */
abstract class BasePolicy
{
    use AuthorizesOwnership;
    use AuthorizesRoles;
}
