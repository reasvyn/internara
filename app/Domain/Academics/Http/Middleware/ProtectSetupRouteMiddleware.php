<?php

declare(strict_types=1);

namespace App\Domain\Academics\Http\Middleware;

use App\Domain\Admin\Aggregates\Setup\Actions\ValidateSetupTokenAction;
use App\Domain\Admin\Aggregates\Setup\Entities\SetupState;
use App\Domain\Admin\Aggregates\Setup\Models\Setup;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

class ProtectSetupRouteMiddleware
{
    public function __construct(
        private ValidateSetupTokenAction $validateToken,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $state = Setup::state();

        if ($state->isInstalled()) {
            return $this->handleInstalled($request, $next, $state);
        }

        if (Session::get('setup.authorized', false)) {
            return $next($request);
        }

        $rateAttempts = (int) config('setup.security.rate_limit_attempts', 20);
        $rateDecay = (int) config('setup.security.rate_limit_decay_seconds', 60);
        $key = 'setup:'.$request->ip();

        $token = $request->query('setup_token') ?? $request->input('setup_token');

        if ($token !== null && $token !== '') {
            try {
                $this->validateToken->execute((string) $token);

                Session::put('setup.authorized', true);
                Session::regenerate();
                RateLimiter::clear($key);

                if ($request->isMethod('POST')) {
                    return redirect()->route('setup');
                }

                return $next($request);
            } catch (\Exception) {
                return $this->throttleOrReject($request, $key, $rateAttempts, $rateDecay);
            }
        }

        return $this->throttleOrReject($request, $key, $rateAttempts, $rateDecay, false);
    }

    private function handleInstalled(Request $request, Closure $next, SetupState $state): Response
    {
        $windowSeconds = (int) config('setup.security.finalization_window_seconds', 30);

        if (Session::get('setup.completed', false) && $state->isWithinFinalizationWindowSeconds($windowSeconds)) {
            return $next($request);
        }

        Session::forget(['setup.authorized', 'setup.token', 'setup.token_input', 'setup.form_data', 'setup.completed']);

        abort(Response::HTTP_NOT_FOUND);
    }

    private function throttleOrReject(Request $request, string $key, int $maxAttempts, int $decaySeconds, bool $hasInvalidToken = true): Response
    {
        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            $seconds = RateLimiter::availableIn($key);

            if ($request->expectsJson()) {
                return response()->json(
                    ['message' => __('setup.rate_limited', ['seconds' => $seconds])],
                    Response::HTTP_TOO_MANY_REQUESTS,
                );
            }

            return response()->view('setup.enter-code', [
                'error' => __('setup.rate_limited', ['seconds' => $seconds]),
            ], Response::HTTP_TOO_MANY_REQUESTS);
        }

        RateLimiter::hit($key, $decaySeconds);

        if ($hasInvalidToken) {
            return $this->rejectToken($request, __('setup.invalid_token'));
        }

        if ($request->expectsJson() || $request->hasHeader('X-Livewire')) {
            return $this->rejectToken($request, __('setup.invalid_token'));
        }

        return response()->view('setup.enter-code');
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
