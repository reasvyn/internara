<?php

declare(strict_types=1);

namespace Modules\Setup\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Modules\Setup\Services\Contracts\SetupService;
use Symfony\Component\HttpFoundation\Response;

class BypassSetupAuthorization
{
    public function __construct(protected SetupService $setupService) {}

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Grant full access during installation phase if authorized via session
        Gate::before(function ($user, $ability) {
            $isSetupAuthorized = session(SetupService::SESSION_SETUP_AUTHORIZED) === true;
            $isAppInstalled = setting('app_installed', false);

            if (! $isAppInstalled && $isSetupAuthorized) {
                return true;
            }
        });

        return $next($request);
    }
}
