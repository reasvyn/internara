<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Facades\File;

/**
 * Provides access to application metadata from app_info.json.
 *
 * S2 - Sustain: Centralized metadata management.
 */
class AppInfo
{
    /**
     * Cached application information.
     */
    private static ?array $info = null;

    /**
     * Get application metadata.
     *
     * @return array<string, mixed>
     */
    public static function all(): array
    {
        Integrity::verify();

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
     * Get a specific metadata value.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        return data_get(self::all(), $key, $default);
    }

    /**
     * Get the application version.
     */
    public static function version(): string
    {
        return (string) self::get('version', '0.0.0');
    }

    /**
     * Get author credits.
     *
     * @return array<string, string>
     */
    public static function author(): array
    {
        return self::get('author', []);
    }

    /**
     * Get the application logo URL.
     */
    public static function logo(): string
    {
        return (string) self::get('logo_url', asset('/brand/logo.png'));
    }

    /**
     * Clear the cached application information.
     * Useful for testing environments.
     */
    public static function clearCache(): void
    {
        self::$info = null;
    }
}
