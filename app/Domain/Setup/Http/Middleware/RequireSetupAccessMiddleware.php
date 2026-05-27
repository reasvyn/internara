<?php

declare(strict_types=1);

namespace App\Domain\Setup\Http\Middleware;

use App\Domain\Core\Support\CacheKeys;
use App\Domain\Setup\Models\Setup;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class RequireSetupAccessMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($this->isInstalledCached()) {
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

    private function isInstalledCached(): bool
    {
        return (bool) Cache::remember(CacheKeys::SETUP_INSTALLED, 3600, function () {
            return Setup::state()->isInstalled();
        });
    }
}
