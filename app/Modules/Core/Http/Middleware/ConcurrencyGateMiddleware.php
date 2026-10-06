<?php

declare(strict_types=1);

namespace App\Modules\Core\Http\Middleware;

use Closure;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class ConcurrencyGateMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('concurrency.enabled', true)) {
            $response = $next($request);
            $response->headers->set('X-Concurrency-Gate', 'bypassed');

            return $response;
        }

        if ($request->isMethodSafe() || $request->isMethod('OPTIONS') || $this->isExempt($request)) {
            $response = $next($request);
            $response->headers->set('X-Concurrency-Gate', 'bypassed');

            return $response;
        }

        $lockKey = $this->resolveLockKey($request);
        $ttl = (int) config('concurrency.lock_ttl_seconds', 30);
        $waitTimeout = (int) config('concurrency.wait_timeout_seconds', 15);

        $lock = Cache::lock($lockKey, $ttl);

        try {
            /** @var Response $response */
            $response = $lock->block($waitTimeout, function () use ($next, $request): Response {
                return $next($request);
            });

            $response->headers->set('X-Concurrency-Gate', 'acquired');

            return $response;
        } catch (LockTimeoutException) {
            return $this->buildTimeoutResponse($request);
        }
    }

    /**
     * Determine whether the request path is exempt from concurrency locking.
     */
    private function isExempt(Request $request): bool
    {
        $exemptPatterns = (array) config('concurrency.exempt_routes', []);

        foreach ($exemptPatterns as $pattern) {
            $normalized = ltrim((string) $pattern, '/');
            if ($request->is($normalized)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Resolve the cache lock key for the request based on user session or global configuration.
     */
    private function resolveLockKey(Request $request): string
    {
        if (config('concurrency.global_write_lock', false)) {
            return (string) config('cache-keys.concurrency_global_lock', 'core.concurrency.global');
        }

        $user = $request->user();
        if ($user !== null) {
            $prefix = (string) config('cache-keys.concurrency_user_lock', 'core.concurrency.user.');

            return $prefix.(string) $user->getAuthIdentifier();
        }

        $prefix = (string) config('cache-keys.concurrency_guest_lock', 'core.concurrency.guest.');
        $identifier = $request->hasSession() ? $request->session()->getId() : ($request->ip() ?? 'unknown');

        return $prefix.(string) $identifier;
    }

    /**
     * Build HTTP 429 response when the concurrency lock wait times out.
     */
    private function buildTimeoutResponse(Request $request): Response
    {
        $message = __('core.exceptions.concurrency_too_many_requests');

        if ($request->expectsJson() || $request->hasHeader('X-Livewire')) {
            $response = response()->json([
                'message' => $message,
            ], Response::HTTP_TOO_MANY_REQUESTS);
        } else {
            $response = response($message, Response::HTTP_TOO_MANY_REQUESTS);
        }

        $response->headers->set('Retry-After', '1');
        $response->headers->set('X-Concurrency-Gate', 'timeout');

        return $response;
    }
}
