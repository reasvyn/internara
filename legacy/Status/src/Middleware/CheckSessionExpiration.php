<?php

declare(strict_types=1);

namespace Modules\Status\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Status\Services\SessionExpirationService;
use Symfony\Component\HttpFoundation\Response;

class CheckSessionExpiration
{
    public function __construct(private SessionExpirationService $sessionExpiration) {}

    /**
     * Handle an incoming request.
     * Check if user's session has expired and logout if needed.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Only check for authenticated admin/super_admin users
        if (! Auth::check()) {
            return $next($request);
        }

        $user = Auth::user();

        // Only apply to admin roles (security-focused)
        if (! \in_array($user->role, ['super_admin', 'admin'], true)) {
            return $next($request);
        }

        $sessionId = $request->getSession()->getId();

        // Check if session has expired
        if ($this->sessionExpiration->isExpired($sessionId)) {
            Auth::logout();
            $request->getSession()->invalidate();
            $request->getSession()->regenerateToken();

            return redirect('/login')->with(
                'error',
                'Sesi Anda telah berakhir. Silakan login kembali untuk melanjutkan.',
            );
        }

        // Update last activity
        $this->sessionExpiration->updateLastActivity($sessionId);

        // Add session expiration info to response headers for JS to show warning
        return $next($request)
            ->header(
                'X-Session-Expires-In',
                (string) $this->sessionExpiration->getRemainingMinutes($sessionId),
            )
            ->header(
                'X-Session-Approaching-Expiration',
                $this->sessionExpiration->isApproachingExpiration($sessionId) ? 'true' : 'false',
            );
    }
}
