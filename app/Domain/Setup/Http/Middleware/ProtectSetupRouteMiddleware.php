<?php

declare(strict_types=1);

namespace App\Domain\Setup\Http\Middleware;

use App\Domain\Setup\Actions\ValidateSetupTokenAction;
use App\Domain\Setup\Models\Setup;
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
        $rateAttempts = (int) config('setup.security.rate_limit_attempts', 20);
        $rateDecay = (int) config('setup.security.rate_limit_decay_seconds', 60);
        $finalizationMinutes = (int) config('setup.security.finalization_window_minutes', 5);

        $state = Setup::state();

        if ($state->isInstalled()) {
            if ($state->isWithinFinalizationWindow($finalizationMinutes) && Session::get('setup.authorized', false)) {
                return $next($request);
            }

            abort(Response::HTTP_NOT_FOUND);
        }

        if (Session::get('setup.authorized', false)) {
            return $next($request);
        }

        $key = 'setup:'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, $rateAttempts)) {
            $seconds = RateLimiter::availableIn($key);

            return response()->json(
                [
                    'message' => __('setup.rate_limited', ['seconds' => $seconds]),
                ],
                Response::HTTP_TOO_MANY_REQUESTS,
            );
        }

        RateLimiter::hit($key, $rateDecay);

        $token = $request->query('setup_token') ?? $request->input('setup_token');

        if ($token !== null && $token !== '') {
            try {
                $this->validateToken->execute((string) $token);

                Session::put('setup.authorized', true);

                if ($request->isMethod('POST')) {
                    return redirect()->route('setup');
                }

                return $next($request);
            } catch (\Exception) {
                return $this->rejectToken($request, __('setup.invalid_token'));
            }
        }

        if ($request->expectsJson() || $request->hasHeader('X-Livewire')) {
            return $this->rejectToken($request, __('setup.invalid_token'));
        }

        return $this->renderCodeEntry();
    }

    private function renderCodeEntry(): Response
    {
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
