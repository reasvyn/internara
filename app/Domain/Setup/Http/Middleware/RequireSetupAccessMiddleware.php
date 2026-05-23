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

        $path = public_path(urldecode($request->path()));

        if (file_exists($path) && ! is_dir($path)) {
            return $next($request);
        }

        if ($request->hasHeader('X-Livewire') || $request->is('setup')) {
            return $next($request);
        }

        return redirect()->route('setup');
    }
}
