<?php

declare(strict_types=1);

namespace App\Domain\Core\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        foreach (config('security-headers.headers', []) as $key => $value) {
            $response->headers->set($key, $value);
        }

        if (config('security-headers.csp_enabled', true)) {
            $csp = (string) (config('security-headers.csp') ?? "default-src 'self'");
            $response->headers->set('Content-Security-Policy', $csp);
        }

        return $response;
    }
}
