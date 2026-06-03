<?php

declare(strict_types=1);

namespace App\Domain\Settings\Aggregates\Setting\Support;

use App\Domain\Core\Support\CacheKeys;
use App\Domain\Core\Support\Integrity;
use App\Domain\Core\Support\SmartLogger;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

final class AppInfo
{
    private static ?array $info = null;

    public static function all(): array
    {
        Integrity::verify();

        if (self::$info === null) {
            self::$info = Cache::remember(CacheKeys::APPINFO_METADATA, 86400, function () {
                $path = base_path('composer.json');

                if (! File::exists($path)) {
                    return [];
                }

                try {
                    $rawContent = File::get($path);
                    $data = json_decode($rawContent, true);

                    if (json_last_error() !== JSON_ERROR_NONE) {
                        SmartLogger::error('Failed to parse composer.json metadata')
                            ->withPayload([
                                'file' => $path,
                                'json_error' => json_last_error_msg(),
                            ])
                            ->systemOnly()
                            ->save();

                        return [];
                    }

                    $data = is_array($data) ? $data : [];
                    $author = $data['authors'][0] ?? [];

                    if (isset($author['homepage']) && ! isset($author['github'])) {
                        $author['github'] = $author['homepage'];
                    }

                    return [
                        'name' => $data['display_name'] ?? $data['name'] ?? 'Laravel',
                        'version' => $data['version'] ?? '1.0.0',
                        'description' => $data['description'] ?? '',
                        'license' => $data['license'] ?? '',
                        'author' => $author,
                        'support' => $data['support'] ?? [],
                    ];
                } catch (\Throwable $e) {
                    SmartLogger::error('Failed to read composer.json metadata')
                        ->withPayload([
                            'file' => $path,
                            'error' => $e->getMessage(),
                        ])
                        ->systemOnly()
                        ->save();

                    return [];
                }
            });
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
        return (string) self::get('logo', asset('/brand/logo.png'));
    }

    public static function clearCache(): void
    {
        self::$info = null;
        Cache::forget(CacheKeys::APPINFO_METADATA);
    }
}
