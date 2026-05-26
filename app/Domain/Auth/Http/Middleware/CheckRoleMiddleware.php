<?php

declare(strict_types=1);

namespace App\Domain\Auth\Http\Middleware;

use App\Domain\Core\Support\SmartLogger;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRoleMiddleware
{
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $user = $request->user();

        if ($user === null) {
            if ($request->expectsJson() || $request->hasHeader('X-Livewire')) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }

            return redirect()->route('login');
        }

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

        if ($user->hasAnyRole($normalizedRoles)) {
            return $next($request);
        }

        SmartLogger::warning('Unauthorized role access attempt blocked by CheckRoleMiddleware middleware')
            ->withPayload([
                'user_id' => $user->id,
                'required_roles' => $normalizedRoles,
                'user_roles' => $user->getRoleNames()->toArray(),
                'path' => $request->path(),
                'method' => $request->method(),
                'ip' => $request->ip(),
            ])
            ->systemOnly()
            ->save();

        if ($request->expectsJson()) {
            return response()->json(
                [
                    'message' => 'Security Access Denied. Your identity profile does not have the required clearance level.',
                ],
                403,
            );
        }

        abort(403, __('Access denied.'));
    }
}
