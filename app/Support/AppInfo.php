<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

final class AppInfo
{
    private static ?array $info = null;

    public static function all(): array
    {
        Integrity::verify();

        if (self::$info === null) {
            $path = base_path('composer.json');

            if (! File::exists($path)) {
                self::$info = [];
            } else {
                try {
                    $rawContent = File::get($path);
                    $data = json_decode($rawContent, true);

                    if (json_last_error() !== JSON_ERROR_NONE) {
                        Log::error('Failed to parse composer.json metadata', [
                            'file' => $path,
                            'json_error' => json_last_error_msg(),
                        ]);

                        self::$info = [];
                    } else {
                        $data = is_array($data) ? $data : [];
                        $author = $data['authors'][0] ?? [];

                        if (isset($author['homepage']) && ! isset($author['github'])) {
                            $author['github'] = $author['homepage'];
                        }

                        self::$info = [
                            'name' => $data['display_name'] ?? $data['name'] ?? 'Laravel',
                            'version' => $data['version'] ?? '1.0.0',
                            'description' => $data['description'] ?? '',
                            'license' => $data['license'] ?? '',
                            'author' => $author,
                            'support' => $data['support'] ?? [],
                        ];
                    }
                } catch (\Throwable $e) {
                    Log::error('Failed to read composer.json metadata', [
                        'file' => $path,
                        'error' => $e->getMessage(),
                    ]);

                    self::$info = [];
                }
            }
        }

        return self::$info;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return data_get(self::all(), $key, $default);
    }

    public static function version(): string
    {
        return (string) self::get('version', '1.0.0');
    }

    public static function author(): array
    {
        return self::get('author', []);
    }

    public static function logo(): string
    {
        return (string) self::get('logo_url', asset('/brand/logo.png'));
    }

    public static function clearCache(): void
    {
        self::$info = null;
    }
}
