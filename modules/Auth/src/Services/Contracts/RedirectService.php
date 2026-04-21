<?php

declare(strict_types=1);

namespace Modules\Auth\Services\Contracts;

use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Defines the contract for redirection logic after authentication.
 */
interface RedirectService
{
    /**
     * Get the target URL for the authenticated user.
     */
    public function getTargetUrl(Authenticatable $user): string;

    /**
     * Get the target URL bypassing the email verification gate.
     * Used when the user explicitly skips email verification.
     */
    public function getTargetUrlSkipVerification(Authenticatable $user): string;
}
