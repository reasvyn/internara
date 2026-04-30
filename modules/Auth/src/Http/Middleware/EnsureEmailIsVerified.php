<?php

declare(strict_types=1);

namespace Modules\Auth\Http\Middleware;

use Closure;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Custom email verification middleware.
 *
 * Differs from Laravel's default EnsureEmailIsVerified in two ways:
 * 1. Users with no email address are allowed through — they cannot verify
 *    an email they don't have. A soft dashboard banner handles awareness.
 * 2. Respects the `require_email_verification` application setting;
 *    when disabled, no gate is enforced regardless of email status.
 */
class EnsureEmailIsVerified
{
    public function handle(
        Request $request,
        Closure $next,
        ?string $redirectToRoute = null,
    ): Response {
        $user = $request->user();

        if (!$user) {
            return $next($request);
        }

        // Gate disabled system-wide → pass through.
        if (!setting('require_email_verification', true)) {
            return $next($request);
        }

        // User has no email address → cannot verify; pass through.
        if (!$user->email) {
            return $next($request);
        }

        // User already verified → pass through.
        if (!($user instanceof MustVerifyEmail) || $user->hasVerifiedEmail()) {
            return $next($request);
        }

        // Unverified email, no skip — redirect to verification notice.
        return $request->expectsJson()
            ? abort(403, 'Your email address is not verified.')
            : redirect()->guest(route($redirectToRoute ?? 'verification.notice'));
    }
}
