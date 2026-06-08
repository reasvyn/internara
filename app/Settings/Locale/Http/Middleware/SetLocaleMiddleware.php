<?php

declare(strict_types=1);

namespace App\Settings\Locale\Http\Middleware;

use App\Settings\Locale\Support\Locale;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocaleMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = Locale::current();

        App::setLocale($locale);

        return $next($request);
    }
}
