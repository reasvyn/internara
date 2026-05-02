<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\Setup\SetupService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * S1 - Secure: Auto-redirect uninstalled users to setup wizard.
 * Prevents access to non-setup routes if app is not installed.
 */
class RequireSetupAccess
{
    public function __construct(
        protected readonly SetupService $setupService
    ) {}

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $isInstalled = $this->setupService->isInstalled();
        $isSetupRoute = $request->is('setup*');

        // Jika sudah terinstal, larang akses ke rute setup
        if ($isInstalled) {
            if ($isSetupRoute) {
                return redirect()->route('login')->with('info', __('setup.already_installed'));
            }

            return $next($request);
        }

        // Jika belum terinstal, dan bukan rute setup/livewire -> paksa ke setup
        if (! $isSetupRoute && ! $request->is('livewire/*')) {
            return redirect()->route('setup');
        }

        return $next($request);
    }
}
