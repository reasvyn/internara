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
        $locale = $request->cookie('locale') ?? Locale::current();
        if (! Locale::isSupported($locale)) {
            $locale = Locale::current();
        }

        App::setLocale($locale);

        return $next($request);
    }
}
