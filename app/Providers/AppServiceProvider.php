<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (is_debug_mode()) {
            $this->app->make('translator')->handleMissingKeysUsing(
                fn (string $key, array $replace, ?string $locale) => \Illuminate\Support\Facades\Log::warning(
                    "Translation key missing: '{$key}' (Locale: {$locale})"
                )
            );
        }
    }
}
