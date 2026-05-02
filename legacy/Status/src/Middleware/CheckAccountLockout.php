<?php

declare(strict_types=1);

namespace Modules\Status\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Modules\Status\Enums\Status;
use Modules\Status\Services\AccountLockoutService;
use Modules\User\Models\User;

/**
 * CheckAccountLockout Middleware
 *
 * Guards against brute-force login attacks by:
 * 1. Tracking failed login attempts per user + IP
 * 2. Locking account after 5 failed attempts in 30 min window
 * 3. Blocking further login attempts while locked
 * 4. Auto-unlocking after 30 minutes
 * 5. Notifying user of lockout via email
 *
 * Applied to auth/login and password reset routes
 */
class CheckAccountLockout
{
    private AccountLockoutService $lockoutService;

    public function __construct(AccountLockoutService $lockoutService)
    {
        $this->lockoutService = $lockoutService;
    }

    public function handle(Request $request, Closure $next)
    {
        // Only apply to login/password reset attempts
        if (
            ! in_array($request->route()?->getName(), [
                'login',
                'password.request',
                'password.reset',
            ])
        ) {
            return $next($request);
        }

        // Check if account is locked OUT (SUSPENDED due to failed attempts)
        if ($request->has('email')) {
            $user = User::where('email', $request->input('email'))->first();

            if ($user && $this->lockoutService->isLockedOut($user, $request->ip())) {
                Log::warning('Login attempt blocked - account locked out', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'ip' => $request->ip(),
                ]);

                return response()->view(
                    'auth.locked-out',
                    [
                        'user' => $user,
                        'unlock_time' => $this->lockoutService->getUnlockTime($user),
                    ],
                    423,
                ); // 423 Locked status code
            }
        }

        return $next($request);
    }
}
