<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\Setup\SetupService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

/**
 * Protects setup routes from unauthorized access.
 *
 * When installed → returns 404 (route appears to not exist).
 * When not installed → requires valid setup token with rate limiting.
 */
class ProtectSetupRouteMiddleware
{
    public function __construct(protected SetupService $setupService) {}

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // When installed, only allow access during the 5-minute
        // finalization window (step 7) so the user can see the completion summary.
        // After that window, /setup returns 404 as if it never existed.
        if ($this->setupService->isInstalled()) {
            if (
                $this->setupService->getCurrentStep() === 7 &&
                $this->setupService->isFinalizationWindowActive()
            ) {
                return $next($request);
            }

            abort(Response::HTTP_NOT_FOUND);
        }

        // Rate limit: 20 attempts per 60 seconds per IP
        $key = 'setup:'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, 20)) {
            $seconds = RateLimiter::availableIn($key);

            return response()->json(
                [
                    'message' => __('setup.rate_limited', ['seconds' => $seconds]),
                ],
                Response::HTTP_TOO_MANY_REQUESTS,
            );
        }

        RateLimiter::hit($key, 60);

        // Allow access if session is already authorized
        if ($this->setupService->isSessionAuthorized()) {
            return $next($request);
        }

        // Validate token from query parameter, session, or Referer header (for Livewire)
        $token = $request->query('setup_token')
            ?? $request->session()->get('setup.token_input');

        // S1: If still null, check if this is a Livewire request and try to extract from referer
        if ($token === null && $request->hasHeader('X-Livewire')) {
            $referer = $request->header('referer');
            if ($referer) {
                parse_str(parse_url($referer, PHP_URL_QUERY) ?? '', $query);
                $token = $query['setup_token'] ?? null;
            }
        }

        if ($token === null || ! $this->setupService->validateToken((string) $token)) {
            if ($request->hasHeader('X-Livewire')) {
                return response()->json([
                    'message' => __('setup.invalid_token'),
                    'redirect' => route('login'),
                ], 403);
            }

            abort(Response::HTTP_FORBIDDEN, __('setup.invalid_token'));
        }

        // Authorize session for subsequent requests
        $this->setupService->authorizeSession((string) $token);
        $request->session()->put('setup.token_input', $token);

        return $next($request);
    }
}
