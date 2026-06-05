<?php

declare(strict_types=1);

namespace App\Core\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
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
            $csp = config('security-headers.csp', "default-src 'self'");
            $csp = $this->injectViteDevUrl($csp);
            $response->headers->set('Content-Security-Policy', (string) $csp);
        }

        return $response;
    }

    private function injectViteDevUrl(string $csp): string
    {
        $hotPath = public_path('hot');

        if (! File::exists($hotPath)) {
            return $csp;
        }

        $viteUrl = trim(File::get($hotPath));

        if ($viteUrl === '') {
            return $csp;
        }

        $directives = [
            'script-src' => $viteUrl,
            'style-src' => $viteUrl,
            'img-src' => $viteUrl,
            'font-src' => $viteUrl,
            'connect-src' => str_replace('http', 'ws', $viteUrl).' '.$viteUrl,
        ];

        foreach ($directives as $directive => $url) {
            if (preg_match('/'.preg_quote($directive, '/')."\s+'self'[^;]*;/", $csp)) {
                $csp = preg_replace(
                    '/('.preg_quote($directive, '/')."\s+'self'[^;]*);/",
                    '$1 '.$url.';',
                    $csp,
                );
            }
        }

        return $csp;
    }
}
