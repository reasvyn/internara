<?php

declare(strict_types=1);

namespace Modules\Shared\Support;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;

/**
 * Utility class for managing authoritative application metadata.
 *
 * This class provides a portable, project-agnostic way to access 
 * technical metadata from the system's app_info.json record.
 */
final class AppInfo
{
    /**
     * Cached application information.
     */
    private static ?array $info = null;

    /**
     * Retrieves a specific metadata value by its key.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        return Arr::get(self::all(), $key, $default);
    }

    /**
     * Retrieves the entire metadata collection.
     */
    public static function all(): array
    {
        if (self::$info === null) {
            $path = base_path('app_info.json');

            if (! File::exists($path)) {
                self::$info = [];
            } else {
                self::$info = json_decode(File::get($path), true) ?? [];
            }
        }

        return self::$info;
    }

    /**
     * Retrieves the application version.
     */
    public static function version(): string
    {
        return (string) self::get('version', '0.0.0');
    }

    /**
     * Clears the cached application information.
     * Useful for testing environments.
     */
    public static function clearCache(): void
    {
        self::$info = null;
    }
}
