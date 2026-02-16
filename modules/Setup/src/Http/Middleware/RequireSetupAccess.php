<?php

declare(strict_types=1);

namespace Modules\Setup\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Livewire\Livewire;
use Modules\Setup\Services\Contracts\SetupService;
use Symfony\Component\HttpFoundation\Response;

class RequireSetupAccess
{
    public function __construct(protected SetupService $setupService) {}

    /**
     * Handle an incoming request.
     *
     * @param \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response) $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 1. If already installed and trying to access setup route, hide it (404)
        if ($this->setupService->isAppInstalled() && $this->isSetupRoute($request)) {
            return abort(404);
        }

        // 2. If NOT installed, and NOT trying to access setup route, redirect to setup
        if (! $this->setupService->isAppInstalled() && ! $this->isSetupRoute($request)) {
            // Bypass specific requests
            if ($this->bypassSpecificRequests($request)) {
                return $next($request);
            }

            return redirect()->route('setup.welcome');
        }

        return $next($request);
    }

    protected function bypassSpecificRequests(Request $request): bool
    {
        return app()->runningInConsole() ||
            $this->isLivewireRequest($request) ||
            app()->environment('testing');
    }

    /**
     * Check if the current request is for a setup route.
     */
    private function isSetupRoute(Request $request): bool
    {
        return $request->routeIs('setup.*');
    }

    private function isLivewireRequest(Request $request): bool
    {
        // for UI Interaction and file upload
        return Livewire::isLivewireRequest() || $request->is('livewire/*');
    }
}
