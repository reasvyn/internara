<?php

declare(strict_types=1);

namespace Modules\Dashboard\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardRedirectMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();

        if ($user) {
            // Check in order of precedence (Admin > Teacher > Student)
            if ($user->hasRole('admin') || $user->hasRole('super-admin')) {
                // If the user is an admin/super-admin and is not already on the admin dashboard route, redirect them.
                if (! $request->routeIs('dashboard.admin')) {
                    return redirect()->route('dashboard.admin');
                }
            } elseif ($user->hasRole('teacher')) {
                // If the user is a teacher and is not already on the teacher dashboard route, redirect them.
                if (! $request->routeIs('dashboard.teacher')) {
                    return redirect()->route('dashboard.teacher');
                }
            } elseif ($user->hasRole('mentor')) {
                // If the user is a mentor and is not already on the mentor dashboard route, redirect them.
                if (! $request->routeIs('dashboard.mentor')) {
                    return redirect()->route('dashboard.mentor');
                }
            } elseif ($user->hasRole('student')) {
                // If the user is a student, they should be on the main 'dashboard' route.
                // If they are on it, let them pass. If not, redirect them.
                if (! $request->routeIs('dashboard')) {
                    return redirect()->route('dashboard');
                }
            } else {
                // If the user has none of the required roles, deny access.
                abort(403, 'Unauthorized action.');
            }
        }

        // If the user is not authenticated, the 'auth' middleware will handle it.
        // Or if the user is already on the correct dashboard, proceed.
        return $next($request);
    }
}
