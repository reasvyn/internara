<?php

declare(strict_types=1);

namespace App\Domain\Core\Support;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

/**
 * Provides access to application metadata from app_info.json.
 *
 * S2 - Sustain: Centralized metadata management.
 */
final class AppInfo
{
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
            $isComposer = false;

            if (! File::exists($path)) {
                $path = base_path('composer.json');
                $isComposer = true;
            }

            if (! File::exists($path)) {
                self::$info = [];
            } else {
                try {
                    $rawContent = File::get($path);
                    $data = json_decode($rawContent, true);

                    if (json_last_error() !== JSON_ERROR_NONE) {
                        Log::error('Failed to parse JSON metadata file', [
                            'file' => $path,
                            'json_error' => json_last_error_msg(),
                        ]);

                        self::$info = [];
                    } else {
                        $data = is_array($data) ? $data : [];

                        if ($isComposer) {
                            $author = $data['authors'][0] ?? [];

                            if (isset($author['homepage']) && ! isset($author['github'])) {
                                $author['github'] = $author['homepage'];
                            }

                            self::$info = [
                                'name' => $data['name'] ?? 'Laravel',
                                'version' => $data['version'] ?? '1.0.0',
                                'description' => $data['description'] ?? '',
                                'license' => $data['license'] ?? '',
                                'author' => $author,
                                'support' => $data['support'] ?? [],
                            ];
                        } else {
                            self::$info = $data;
                        }
                    }
                } catch (\Throwable $e) {
                    Log::error('Failed to read application metadata file', [
                        'file' => $path,
                        'error' => $e->getMessage(),
                    ]);

                    self::$info = [];
                }
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
        return (string) self::get('version', '1.0.0');
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
