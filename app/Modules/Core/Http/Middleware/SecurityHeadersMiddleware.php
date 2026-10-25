<?php

declare(strict_types=1);

namespace App\Modules\Core\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeadersMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $nonce = $this->prepareNonce();

        $response = $next($request);

        foreach (config('security-headers.headers', []) as $key => $value) {
            $response->headers->set($key, $value);
        }

        $this->applyCsp($response, $nonce);
        $this->applyHsts($request, $response);

        return $response;
    }

    /**
     * Generate the per-request CSP nonce and publish it to Vite.
     *
     * Must run before the response is rendered: @vite, @livewireStyles and
     *
     * @livewireScripts read Vite::cspNonce() while building the markup.
     */
    private function prepareNonce(): ?string
    {
        if (! config('security-headers.csp_enabled', true)) {
            return null;
        }

        if (! config('security-headers.csp_nonce_enabled', true)) {
            return null;
        }

        $nonce = base64_encode(random_bytes(16));

        Vite::useCspNonce($nonce);

        return $nonce;
    }

    private function applyCsp(Response $response, ?string $nonce): void
    {
        if (! config('security-headers.csp_enabled', true)) {
            return;
        }

        $csp = (string) config('security-headers.csp', "default-src 'self'");
        $csp = $this->injectViteDevUrl($csp);
        $csp = $this->injectNonce($csp, $nonce);

        $response->headers->set('Content-Security-Policy', $csp);
    }

    private function applyHsts(Request $request, Response $response): void
    {
        if (! config('security-headers.hsts_enabled', false)) {
            return;
        }

        // RFC 6797: the header is meaningless over plain HTTP, and emitting it
        // there would advertise transport security the response does not have.
        if (! $request->isSecure()) {
            return;
        }

        $maxAge = config('security-headers.hsts_max_age', 31536000);
        $value = "max-age={$maxAge}";

        if (config('security-headers.hsts_include_subdomains', true)) {
            $value .= '; includeSubDomains';
        }

        if (config('security-headers.hsts_preload', false)) {
            $value .= '; preload';
        }

        $response->headers->set('Strict-Transport-Security', $value);
    }

    /**
     * Add the request nonce to script-src and drop 'unsafe-inline'.
     *
     * A nonce makes 'unsafe-inline' inert in every modern browser, so keeping
     * it would only mislead readers into thinking inline scripts are allowed.
     */
    private function injectNonce(string $csp, ?string $nonce): string
    {
        if ($nonce === null) {
            return $csp;
        }

        if (! preg_match("/script-src\s+([^;]*);/", $csp, $matches)) {
            return $csp;
        }

        $sources = preg_replace("/'unsafe-inline'\s*/", '', $matches[1]) ?? $matches[1];
        $sources = trim($sources);

        return preg_replace(
            "/script-src\s+[^;]*;/",
            "script-src 'nonce-{$nonce}'{$this->prefix($sources)};",
            $csp,
            1,
        ) ?? $csp;
    }

    private function prefix(string $sources): string
    {
        return $sources === '' ? '' : ' '.$sources;
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
