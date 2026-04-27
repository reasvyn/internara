<?php

declare(strict_types=1);

namespace Modules\Setup\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Modules\Admin\Services\Contracts\SuperAdminService;
use Modules\Setting\Services\Contracts\SettingService;
use Modules\Setup\Services\Contracts\AppSetupService;

class ProtectSetupRoute
{
    public function __construct(
        protected AppAppSetupService $setupService,
        protected SuperAdminService $superAdminService,
        protected SettingService $settingService,
    ) {}

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        // [S1 - Secure] Apply Rate Limiting for Setup Routes
        $key = 'setup_throttle:' . $request->ip();
        if (RateLimiter::tooManyAttempts($key, 20)) {
            $seconds = RateLimiter::availableIn($key);

            return abort(429, __('ui::messages.too_many_requests', ['seconds' => $seconds]));
        }
        RateLimiter::hit($key, 60);

        // [S1 - Secure] Check installation status directly
        $isInstalled = $this->setupService->isAppInstalled(true);

        // 1. Total lockdown if application is already installed
        if ($isInstalled) {
            return abort(404);
        }

        // 2. Enforce Signed URL validation or Authorized Session
        $isAuthorized = $request->session()->get(AppAppSetupService::SESSION_SETUP_AUTHORIZED);

        // [S1 - Secure] Only grant new session authorization if valid signature OR valid token is present
        if (!$isAuthorized && ($request->hasValidSignature() || $this->hasValidToken($request))) {
            $request->session()->put(AppAppSetupService::SESSION_SETUP_AUTHORIZED, true);
            $isAuthorized = true;
        }

        // Verify authorized session AND ensure setup_token still exists in DB
        $storedToken = $this->settingService->getValue('setup_token');
        if (!$isAuthorized || empty($storedToken)) {
            return abort(403, __('exception::messages.unauthorized_setup_access'));
        }

        if ($this->shouldRedirectToCompletion($request)) {
            return redirect()->route('setup.complete');
        }

        return $next($request);
    }

    protected function shouldRedirectToCompletion(Request $request): bool
    {
        return !$request->routeIs('setup.complete') && $this->isFinalizationOnlyStepRemaining();
    }

    protected function isFinalizationOnlyStepRemaining(): bool
    {
        foreach ($this->setupStepsBeforeCompletion() as $step) {
            if (!$this->setupService->isStepCompleted($step, true)) {
                return false;
            }
        }

        return !$this->setupService->isStepCompleted(AppAppSetupService::STEP_COMPLETE, true);
    }

    /**
     * @return array<int, string>
     */
    protected function setupStepsBeforeCompletion(): array
    {
        return [
            AppAppSetupService::STEP_WELCOME,
            AppAppSetupService::STEP_ENVIRONMENT,
            AppAppSetupService::STEP_SCHOOL,
            AppAppSetupService::STEP_ACCOUNT,
            AppAppSetupService::STEP_DEPARTMENT,
            AppAppSetupService::STEP_INTERNSHIP,
            AppAppSetupService::STEP_SYSTEM,
        ];
    }

    protected function superAdminExists(): bool
    {
        return Cache::remember(
            'user.super_admin',
            now()->addDay(),
            fn() => $this->superAdminService->exists(),
        );
    }

    protected function hasValidToken(Request $request): bool
    {
        $token = $request->query('token');
        $storedToken = $this->settingService->getValue('setup_token');
        $expiresAt = $this->settingService->getValue('setup_token_expires_at');

        // [S1 - Secure] Enforce Token TTL
        if ($expiresAt && now()->parse($expiresAt)->isPast()) {
            return false;
        }

        return $token && $storedToken && is_string($token) && hash_equals($storedToken, $token);
    }
}
