<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware to check if user has required role(s).
 * Supports Spatie Permission role checks with robust error handling.
 *
 * S1 - Secure: Proper authorization check before accessing resources, logs unauthorized access.
 * S2 - Sustain: Clear error messages for unauthorized access, handles API and Web requests consistently.
 */
class CheckRoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param string|array<int, string> ...$roles
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $user = $request->user();

        if ($user === null) {
            return $request->expectsJson()
                ? response()->json(['message' => 'Unauthenticated.'], 401)
                : redirect()->route('login');
        }

        // Normalize roles: handle pipe-separated strings ('admin|student') and arrays
        $normalizedRoles = [];
        foreach ($roles as $role) {
            if (is_array($role)) {
                $normalizedRoles = array_merge($normalizedRoles, $role);
            } elseif (is_string($role) && str_contains($role, '|')) {
                $normalizedRoles = array_merge($normalizedRoles, explode('|', $role));
            } else {
                $normalizedRoles[] = $role;
            }
        }

        $normalizedRoles = array_map('trim', $normalizedRoles);

        // Check if user has any of the required roles
        if ($user->hasAnyRole($normalizedRoles)) {
            return $next($request);
        }

        // S1: Log unauthorized access attempt with sufficient context
        logger()->warning('Unauthorized role access attempt blocked by CheckRoleMiddleware middleware', [
            'user_id' => $user->id,
            'required_roles' => $normalizedRoles,
            'user_roles' => $user->getRoleNames()->toArray(),
            'path' => $request->path(),
            'method' => $request->method(),
            'ip' => $request->ip(),
        ]);

        if ($request->expectsJson()) {
            return response()->json(
                [
                    'message' => 'Security Access Denied. Your identity profile does not have the required clearance level.',
                ],
                403,
            );
        }

        abort(
            403,
            'Your identity profile does not have the required clearance level to access this encrypted node.',
        );
    }
}
