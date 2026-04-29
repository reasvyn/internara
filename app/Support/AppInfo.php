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
     * Get application metadata.
     *
     * @return array<string, mixed>
     */
    public static function all(): array
    {
        $path = base_path('app_info.json');

        if (!File::exists($path)) {
            return [];
        }

        return json_decode(File::get($path), true) ?? [];
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
        return self::get('version', '0.0.0');
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
}
