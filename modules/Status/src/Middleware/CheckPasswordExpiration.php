<?php

declare(strict_types=1);

namespace Modules\Status\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Status\Services\PasswordPolicyService;

/**
 * CheckPasswordExpiration Middleware
 *
 * Enforces password expiration policies for authenticated users.
 * Redirects to password change form if password has expired or will expire soon.
 *
 * Excluded routes:
 * - /password/change (password change form itself)
 * - /logout (logout action)
 * - /login (login page)
 * - /password/update (password update endpoint)
 * - /api/* (API routes bypass this check)
 */
class CheckPasswordExpiration
{
    private PasswordPolicyService $passwordPolicyService;

    private const EXCLUDED_PATHS = [
        '/password/change',
        '/password/update',
        '/logout',
        '/login',
        '/api',
    ];

    public function __construct(PasswordPolicyService $passwordPolicyService)
    {
        $this->passwordPolicyService = $passwordPolicyService;
    }

    public function handle(Request $request, Closure $next)
    {
        // Skip for unauthenticated users
        if (!auth()->check()) {
            return $next($request);
        }

        // Skip for excluded paths
        if ($this->isExcludedPath($request->path())) {
            return $next($request);
        }

        $user = auth()->user();

        // Check if password has expired
        if ($this->passwordPolicyService->isExpired($user)) {
            session()->put('password_expired', true);
            session()->put('force_password_change', true);

            return redirect()->route('password.change.force')
                ->with('warning', '⚠️ Your password has expired. Please change it immediately.');
        }

        // Warn if password expiring soon (within 14 days)
        if ($this->passwordPolicyService->isExpiringSoon($user)) {
            $daysUntilExpiry = $this->passwordPolicyService->getDaysUntilExpiry($user);

            session()->put('password_expiring_soon', true);
            session()->put('days_until_expiry', $daysUntilExpiry);

            // Show warning but allow access
            if ($daysUntilExpiry <= 7) {
                // Less than 7 days: show more prominent warning
                session()->flash('alert', "🔴 Your password expires in {$daysUntilExpiry} day(s). Please change it soon.");
            } elseif ($daysUntilExpiry <= 14) {
                // 7-14 days: show general warning
                session()->flash('info', "🟡 Your password expires in {$daysUntilExpiry} days. Consider changing it.");
            }
        }

        return $next($request);
    }

    /**
     * Check if current request path is excluded from password expiration checks
     *
     * @param string $path
     * @return bool
     */
    private function isExcludedPath(string $path): bool
    {
        foreach (self::EXCLUDED_PATHS as $excludedPath) {
            if (str_starts_with($path, $excludedPath)) {
                return true;
            }
        }

        return false;
    }
}
