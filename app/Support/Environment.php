<?php

declare(strict_types=1);

namespace App\Support;

final class Environment
{
    public static function isDebugMode(): bool
    {
        return (bool) config('app.debug', false);
    }

    public static function isDevelopment(): bool
    {
        return app()->environment('local', 'dev');
    }

    public static function isStaging(): bool
    {
        return app()->environment('staging');
    }

    public static function isTesting(): bool
    {
        return app()->runningUnitTests();
    }

    public static function isMaintenance(): bool
    {
        return app()->isDownForMaintenance();
    }

    public static function isProduction(): bool
    {
        return app()->environment('production');
    }
}
