<?php

declare(strict_types=1);

namespace Modules\Setup\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Setting\Services\Contracts\SettingService;
use Modules\Setup\Services\Contracts\SetupService;
use Modules\User\Services\Contracts\SuperAdminService;

class ProtectSetupRoute
{
    public function __construct(
        protected SetupService $setupService,
        protected SuperAdminService $superAdminService,
        protected SettingService $settingService,
    ) {}

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        // 1. Total lockdown if already installed and SuperAdmin exists
        if ($this->setupService->isAppInstalled() && $this->superAdminExists()) {
            return abort(404);
        }

        // 2. If not installed, enforce Signed URL validation or Authorized Session
        if (! $this->setupService->isAppInstalled()) {
            // Check for valid signature OR valid token
            if ($request->hasValidSignature() || $this->hasValidToken($request)) {
                $request->session()->put('setup_authorized', true);
            }

            // Verify authorized session AND ensure setup_token still exists in DB
            $storedToken = $this->settingService->getValue('setup_token');
            if (! $request->session()->get('setup_authorized') || empty($storedToken)) {
                return abort(
                    403,
                    'Unauthorized setup access. Please use the signed link provided by the CLI.',
                );
            }
        }

        return $next($request);
    }

    protected function superAdminExists(): bool
    {
        return $this->superAdminService->remember(
            cacheKey: 'user.super_admin',
            ttl: now()->addDay(),
            callback: fn (SuperAdminService $service) => $service->exists(),
        );
    }

    protected function hasValidToken(Request $request): bool
    {
        $token = $request->query('token');
        $storedToken = $this->settingService->getValue('setup_token');

        return $token && $storedToken && is_string($token) && hash_equals($storedToken, $token);
    }
}
