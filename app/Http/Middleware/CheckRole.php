<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware to check if user has required role(s).
 *
 * S1 - Secure: Proper authorization check before accessing resources.
 * S2 - Sustain: Clear error messages for unauthorized access.
 */
class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  array<int, string>|string  $roles
     */
    public function handle(Request $request, Closure $next, string|array $roles): Response
    {
        $user = $request->user();

        if ($user === null) {
            return redirect()->route('login');
        }

        // Convert single role to array
        if (is_string($roles)) {
            $roles = explode('|', $roles);
        }

        // Check if user has any of the required roles
        foreach ($roles as $role) {
            if ($user->hasRole(trim($role))) {
                return $next($request);
            }
        }

        // S1: Log unauthorized access attempt
        logger()->warning('Unauthorized access attempt', [
            'user_id' => $user->id,
            'required_roles' => $roles,
            'user_roles' => $user->getRoleNames(),
            'path' => $request->path(),
        ]);

        abort(403, 'Unauthorized. You do not have the required role to access this resource.');
    }
}
