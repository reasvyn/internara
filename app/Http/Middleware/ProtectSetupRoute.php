<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\Setup\SetupService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

/**
 * Protects setup routes from unauthorized access.
 *
 * S1 - Secure: Token validation with timing-safe comparison, rate limiting.
 */
class ProtectSetupRoute
{
    public function __construct(protected SetupService $setupService) {}

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Block access if already installed
        if ($this->setupService->isInstalled()) {
            return redirect()->route('login')->with('info', __('setup.already_installed'));
        }

        // Rate limit: 20 attempts per 60 seconds per IP
        $key = 'setup:' . $request->ip();

        if (RateLimiter::tooManyAttempts($key, 20)) {
            $seconds = RateLimiter::availableIn($key);

            return response()->json([
                'message' => __('setup.rate_limited', ['seconds' => $seconds]),
            ], Response::HTTP_TOO_MANY_REQUESTS);
        }

        RateLimiter::hit($key, 60);

        // Allow access if session is already authorized
        if ($this->setupService->isSessionAuthorized()) {
            return $next($request);
        }

        // Validate token from query parameter or session
        $token = $request->query('setup_token')
            ?? $request->session()->get('setup.token_input');

        // Also check for CLI-generated token file (from setup:install command)
        if ($token === null) {
            $token = $this->getCliToken();
        }

        if ($token === null) {
            // First visit: generate and store token, redirect with token in URL
            $newToken = $this->setupService->generateToken();

            return redirect()->route('setup', ['setup_token' => $newToken]);
        }

        if (! $this->setupService->validateToken((string) $token)) {
            // Also try validating against CLI token file
            if (! $this->validateCliToken((string) $token)) {
                abort(Response::HTTP_FORBIDDEN, __('setup.invalid_token'));
            }
        }

        // Authorize session for subsequent requests
        $this->setupService->authorizeSession();
        $request->session()->put('setup.token_input', $token);

        return $next($request);
    }

    /**
     * Get CLI-generated token from file.
     */
    protected function getCliToken(): ?string
    {
        $path = storage_path('app/.setup_token');

        if (! File::exists($path)) {
            return null;
        }

        $content = File::get($path);
        $parts = explode('|', $content, 2);

        if (count($parts) < 2) {
            return null;
        }

        [$token, $expiresAt] = $parts;

        // Check if token is expired
        if (now()->greaterThan(\Illuminate\Support\Carbon::parse($expiresAt))) {
            File::delete($path);
            return null;
        }

        return $token;
    }

    /**
     * Validate token against CLI token file.
     */
    protected function validateCliToken(string $token): bool
    {
        $cliToken = $this->getCliToken();

        if ($cliToken === null) {
            return false;
        }

        return hash_equals($cliToken, $token);
    }
}
