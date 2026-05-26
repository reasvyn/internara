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

        $decoded = urldecode($request->path());
        $resolved = realpath(public_path($decoded));

        if ($resolved !== false && str_starts_with($resolved, public_path()) && ! is_dir($resolved)) {
            return $next($request);
        }

        if ($request->hasHeader('X-Livewire') || $request->is('setup')) {
            return $next($request);
        }

        return redirect()->route('setup');
    }
}
