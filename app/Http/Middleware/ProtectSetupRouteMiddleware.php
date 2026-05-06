<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Actions\Setup\ValidateSetupTokenAction;
use App\Models\Setup;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

/**
 * Protects setup routes from unauthorized access.
 *
 * When installed → returns 404 (route appears to not exist).
 * When not installed → requires valid setup token with rate limiting.
 */
class ProtectSetupRouteMiddleware
{
    public function __construct(
        private ValidateSetupTokenAction $validateToken,
    ) {}

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // When installed, only allow access during the 5-minute
        // finalization window so the user can see the completion summary.
        // After that window, /setup returns 404 as if it never existed.
        if (Setup::isInstalled()) {
            if ($this->isWithinFinalizationWindow()) {
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
        if (Session::get('setup.authorized', false)) {
            return $next($request);
        }

        // Validate token from query parameter, session, or Referer header (for Livewire)
        $token = $request->query('setup_token')
            ?? $request->session()->get('setup.token_input');

        if ($token === null && $request->hasHeader('X-Livewire')) {
            $referer = $request->header('referer');
            if ($referer) {
                parse_str(parse_url($referer, PHP_URL_QUERY) ?? '', $query);
                $token = $query['setup_token'] ?? null;
            }
        }

        if ($token === null) {
            return $this->rejectToken($request, __('setup.invalid_token'));
        }

        try {
            $this->validateToken->execute((string) $token);
        } catch (\Exception) {
            return $this->rejectToken($request, __('setup.invalid_token'));
        }

        // Authorize session for subsequent requests
        Session::put('setup.authorized', true);
        Session::put('setup.token', $token);
        $request->session()->put('setup.token_input', $token);

        return $next($request);
    }

    private function isWithinFinalizationWindow(int $minutes = 5): bool
    {
        $setup = Setup::first();

        if ($setup === null || $setup->updated_at === null) {
            return false;
        }

        return $setup->updated_at->diffInMinutes(now()) < $minutes;
    }

    private function rejectToken(Request $request, string $message): Response
    {
        if ($request->hasHeader('X-Livewire')) {
            return response()->json([
                'message' => $message,
                'redirect' => route('login'),
            ], 403);
        }

        abort(Response::HTTP_FORBIDDEN, $message);
    }
}
