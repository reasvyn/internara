<?php

declare(strict_types=1);

namespace Modules\Setup\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Modules\Setting\Services\Contracts\SettingService;
use Modules\Admin\Services\Contracts\SuperAdminService;
use Modules\Setup\Services\Contracts\SetupService;

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
        // [S1 - Secure] Apply Rate Limiting for Setup Routes
        // Limit to 20 attempts per minute to prevent DoS on heavy initialization logic
        $key = 'setup_throttle:' . $request->ip();
        if (RateLimiter::tooManyAttempts($key, 20)) {
            $seconds = RateLimiter::availableIn($key);
            return abort(429, __('ui::messages.too_many_requests', ['seconds' => $seconds]));
        }
        RateLimiter::hit($key, 60);

        $isInstalled = Cache::rememberForever('internara.installed', function () {
            return $this->setupService->isAppInstalled(true);
        });

        // 1. Total lockdown if already installed and SuperAdmin exists
        if ($isInstalled && $this->superAdminExists()) {
            return abort(404);
        }

        // 2. If not installed, enforce Signed URL validation or Authorized Session
        if (! $isInstalled) {
            // Check for valid signature OR valid token
            if ($request->hasValidSignature() || $this->hasValidToken($request)) {
                $request->session()->put(SetupService::SESSION_SETUP_AUTHORIZED, true);
            }

            $isAuthorized = $request->session()->get(SetupService::SESSION_SETUP_AUTHORIZED);

            // Verify authorized session AND ensure setup_token still exists in DB
            $storedToken = $this->settingService->getValue('setup_token');
            if (! $isAuthorized || empty($storedToken)) {
                return abort(
                    403,
                    __('exception::messages.unauthorized_setup_access'),
                );
            }

            if ($this->shouldRedirectToCompletion($request)) {
                return redirect()->route('setup.complete');
            }
        }

        return $next($request);
    }

    protected function shouldRedirectToCompletion(Request $request): bool
    {
        return ! $request->routeIs('setup.complete')
            && $this->isFinalizationOnlyStepRemaining();
    }

    protected function isFinalizationOnlyStepRemaining(): bool
    {
        foreach ($this->setupStepsBeforeCompletion() as $step) {
            if (! $this->setupService->isStepCompleted($step, true)) {
                return false;
            }
        }

        return ! $this->setupService->isStepCompleted(SetupService::STEP_COMPLETE, true);
    }

    /**
     * @return array<int, string>
     */
    protected function setupStepsBeforeCompletion(): array
    {
        return [
            SetupService::STEP_WELCOME,
            SetupService::STEP_ENVIRONMENT,
            SetupService::STEP_SCHOOL,
            SetupService::STEP_ACCOUNT,
            SetupService::STEP_DEPARTMENT,
            SetupService::STEP_INTERNSHIP,
            SetupService::STEP_SYSTEM,
        ];
    }

    protected function superAdminExists(): bool
    {
        // For testing environments, bypass the cache to ensure accurate evaluation
        if (is_testing()) {
            return $this->superAdminService->exists();
        }

        return Cache::remember(
            'user.super_admin',
            now()->addDay(),
            fn () => $this->superAdminService->exists(),
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


