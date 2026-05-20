<?php

declare(strict_types=1);

namespace App\Domain\Core\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class LogContext
{
    public function handle(Request $request, Closure $next): Response
    {
        $context = [
            'request_id' => (string) Str::uuid(),
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'ip' => $request->ip(),
        ];

        if ($request->user()) {
            $context['user_id'] = $request->user()->id;
            $context['user_role'] = $request->user()->roles->pluck('name')->first();
        }

        Log::withContext($context);

        $start = microtime(true);
        $response = $next($request);
        $duration = (microtime(true) - $start) * 1000;

        Log::withContext([
            'duration_ms' => round($duration, 2),
            'status' => $response->getStatusCode(),
        ]);

        return $response;
    }
}
