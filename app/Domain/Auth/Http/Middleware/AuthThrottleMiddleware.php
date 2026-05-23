<?php

declare(strict_types=1);

namespace App\Domain\Auth\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class AuthThrottleMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $key = 'auth-throttle:'.$request->ip();

        $maxAttempts = (int) config('auth.throttle.max_attempts', 30);
        $decaySeconds = (int) config('auth.throttle.decay_seconds', 60);

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            $seconds = RateLimiter::availableIn($key);

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => __('auth.throttle', ['seconds' => $seconds]),
                ], Response::HTTP_TOO_MANY_REQUESTS);
            }

            return redirect()->route('login')
                ->with('error', __('auth.throttle', ['seconds' => $seconds]));
        }

        RateLimiter::hit($key, $decaySeconds);

        return $next($request);
    }
}
