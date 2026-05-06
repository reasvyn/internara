<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Setup;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Auto-redirects all requests to /setup when the application is not installed.
 *
 * Applied globally to the web middleware group. Only job: redirect uninstalled
 * users to the setup wizard. Does not block or manage setup access.
 */
class RequireSetupAccessMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Setup::isInstalled()) {
            return $next($request);
        }

        $isSetupRoute = $request->is('setup');
        $isLivewire = $request->is('livewire/*');

        if (! $isSetupRoute && ! $isLivewire) {
            return redirect()->route('setup');
        }

        return $next($request);
    }
}
