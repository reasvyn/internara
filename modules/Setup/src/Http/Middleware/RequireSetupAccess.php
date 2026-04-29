<?php

declare(strict_types=1);

namespace Modules\Setup\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Setup\Services\Contracts\SetupService;

/**
 * Require Setup Access - Redirect logic for setup flow
 *
 * [S1 - Secure] Prevents bypassing setup, hides routes when installed
 * [S2 - Sustain] Clear redirect logic
 * [S3 - Scalable] Works with any auth guard
 */
class RequireSetupAccess
{
    /**
     * Handle incoming request
     */
    public function handle(Request $request, Closure $next): mixed
    {
        $setupService = app(SetupService::class);
        $isInstalled = $setupService->isInstalled();
        $isSetupRoute = $this->isSetupRoute($request);

        // If already installed and trying to access setup route -> 404
        if ($isInstalled && $isSetupRoute) {
            return abort(404);
        }

        // If NOT installed and NOT trying to access setup -> redirect to setup
        if (!$isInstalled && !$isSetupRoute) {
            $setup = $setupService->getSetup();

            // Generate token if not exists
            if ($setup->token_expires_at === null || $setup->isTokenExpired()) {
                $token = $setupService->generateToken();

                return redirect()->route('setup.welcome', ['token' => $token]);
            }

            $token = $setup->getToken();

            return redirect()->route('setup.welcome', ['token' => $token]);
        }

        return $next($request);
    }

    /**
     * Check if current route is a setup route
     */
    protected function isSetupRoute(Request $request): bool
    {
        return str_starts_with($request->path(), 'setup');
    }
}
