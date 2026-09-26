<?php

declare(strict_types=1);

namespace App\Modules\Setting\Domain\Locale\Http\Middleware;

use App\Modules\Setting\Domain\Locale\Support\Locale;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocaleMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = null;

        if ($request->hasSession() && $request->session()->has('locale')) {
            $sessionLocale = $request->session()->get('locale');
            if (is_string($sessionLocale) && Locale::isSupported($sessionLocale)) {
                $locale = $sessionLocale;
            }
        }

        if ($locale === null) {
            $cookieLocale = $request->cookie('locale');
            if (is_string($cookieLocale) && Locale::isSupported($cookieLocale)) {
                $locale = $cookieLocale;
            }
        }

        if ($locale === null) {
            $locale = Locale::current();
        }

        App::setLocale($locale);

        return $next($request);
    }
}
