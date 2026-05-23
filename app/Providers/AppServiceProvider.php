<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domain\Shared\Support\LangChecker;
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
}
