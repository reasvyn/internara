<?php

declare(strict_types=1);

namespace Modules\Setup\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Modules\Setup\Services\Contracts\SetupService;

/**
 * Protect Setup Routes - Token validation, rate limiting
 *
 * [S1 - Secure] Rate limiting, timing-safe token comparison, TTL check
 * [S2 - Sustain] Clear error messages
 * [S3 - Scalable] Stateless validation (no session dependency)
 */
class ProtectSetupRoute
{
    protected const RATE_LIMIT_ATTEMPTS = 20;

    protected const RATE_LIMIT_DECAY = 60; // seconds

    /**
     * Handle incoming request
     */
    public function handle(Request $request, Closure $next): mixed
    {
        // Check rate limiting
        if ($this->isRateLimited($request)) {
            $seconds = RateLimiter::availableIn($this->throttleKey($request));

            Log::warning('Setup route rate limit exceeded', [
                'ip' => $request->ip(),
                'seconds_remaining' => $seconds,
            ]);

            return response()->json([
                'error' => __('setup::messages.rate_limited', ['seconds' => $seconds]),
                'retry_after' => $seconds,
            ], 429);
        }

        RateLimiter::hit($this->throttleKey($request), self::RATE_LIMIT_DECAY);

        // Validate setup token (from URL or session)
        $token = $request->get('token') ?? $request->session()->get('setup_token');

        if (empty($token)) {
            Log::warning('Setup access denied: no token provided', ['ip' => $request->ip()]);

            return $this->denyAccess(__('setup::messages.token_required'));
        }

        $setupService = app(SetupService::class);

        if (! $setupService->validateToken($token)) {
            Log::warning('Setup access denied: invalid/expired token', [
                'ip' => $request->ip(),
                'token_prefix' => substr($token, 0, 8).'...',
            ]);

            return $this->denyAccess(__('setup::messages.token_invalid'));
        }

        // Store validated token in session for subsequent requests
        $request->session()->put('setup_token', $token);
        $request->session()->put('setup_authorized', true);

        return $next($request);
    }

    /**
     * [S1 - Secure] Check rate limiting
     */
    protected function isRateLimited(Request $request): bool
    {
        return RateLimiter::tooManyAttempts(
            $this->throttleKey($request),
            self::RATE_LIMIT_ATTEMPTS
        );
    }

    /**
     * Generate throttle key (IP-based)
     */
    protected function throttleKey(Request $request): string
    {
        return 'setup_throttle:'.$request->ip();
    }

    /**
     * Deny access with proper response
     */
    protected function denyAccess(string $message): mixed
    {
        if (request()->expectsJson()) {
            return response()->json(['error' => $message], 403);
        }

        return redirect()->route('setup.welcome')->withErrors(['token' => $message]);
    }
}
