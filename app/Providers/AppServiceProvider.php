<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domain\Core\Support\LangChecker;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        if ($this->app->hasDebugModeEnabled()) {
            $this->app->extend('translator', fn ($translator) => tap(
                new LangChecker($translator->getLoader(), $translator->getLocale()),
                fn (LangChecker $checker) => $checker->setFallback($translator->getFallback()),
            ));
        }
    }

    public function boot(): void
    {
        RateLimiter::for('admin', fn () => Limit::perMinute(60));

        RateLimiter::for('global', fn (Request $request) => Limit::perMinute(120)->by($request->ip()));
    }
}
