<?php

declare(strict_types=1);

namespace App\Domain\Setup\Http\Middleware;

use App\Domain\Setup\Models\Setup;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireSetupAccessMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Setup::state()->isInstalled()) {
            return $next($request);
        }

        $isSetupRoute = $request->is('setup');
        $isLivewire = $request->hasHeader('X-Livewire') || $request->is('livewire/*');

        if (! $isSetupRoute && ! $isLivewire) {
            return redirect()->route('setup');
        }

        return $next($request);
    }
}
