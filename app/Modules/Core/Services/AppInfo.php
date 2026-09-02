<?php

declare(strict_types=1);

namespace App\Modules\Core\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;

final class AppInfo
{
    private const DEFAULTS = [
        'name' => 'Laravel',
        'version' => '1.0.0',
        'description' => '',
        'license' => '',
        'author' => ['name' => 'Reas Vyn'],
        'support' => [],
        'gitUrl' => 'https://github.com/reasvyn/internara',
    ];

    private static ?array $metadata = null;

    public static function all(): array
    {
        if (self::$metadata === null) {
            self::$metadata = self::load();
        }

        return self::$metadata;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return data_get(self::all(), $key, $default);
    }

    public static function name(): string
    {
        return self::getString('name');
    }

    public static function version(): string
    {
        return self::getString('version');
    }

    public static function description(): string
    {
        return self::getString('description');
    }

    public static function license(): string
    {
        return self::getString('license');
    }

    public static function author(): array
    {
        return Config::get('app.author', self::get('author', self::DEFAULTS['author']));
    }

    public static function authorName(): string
    {
        $author = self::author();

        return is_string($author['name'] ?? null) ? $author['name'] : 'Reas Vyn';
    }

    public static function authorEmail(): string
    {
        $author = self::author();

        return is_string($author['email'] ?? null) ? $author['email'] : '';
    }

    public static function support(): array
    {
        return Config::get('app.support', self::get('support', []));
    }

    public static function gitUrl(): string
    {
        return Config::get('app.git', self::get('gitUrl', self::DEFAULTS['gitUrl']));
    }

    public static function clearCache(): void
    {
        self::$metadata = null;
        Cache::forget(self::cacheKey());
    }

    private static function getString(string $key): string
    {
        $default = self::DEFAULTS[$key] ?? '';

        return (string) Config::get('app.'.$key, self::get($key, $default));
    }

    private static function cacheKey(): string
    {
        return config('cache-keys.appinfo_metadata');
    }

    private static function load(): array
    {
        return Cache::rememberForever(self::cacheKey(), function () {
            return self::readFromComposer();
        });
    }

    private static function readFromComposer(): array
    {
        $path = base_path('composer.json');

        if (! File::exists($path)) {
            return self::DEFAULTS;
        }

        try {
            $rawContent = File::get($path);
            $data = json_decode($rawContent, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                self::logJsonError($path);

                return self::DEFAULTS;
            }

            return self::extractMetadata(is_array($data) ? $data : []);
        } catch (\Throwable $e) {
            self::logReadError($path, $e);

            return self::DEFAULTS;
        }
    }

    private static function extractMetadata(array $data): array
    {
        $author = $data['authors'][0] ?? [];

        if (isset($author['homepage']) && ! isset($author['github'])) {
            $author['github'] = $author['homepage'];
        }

        $authorHomepage = $author['homepage'] ?? '';
        $authorGithub = $author['github'] ?? $authorHomepage;

        return [
            'name' => $data['display_name'] ?? ($data['name'] ?? 'Laravel'),
            'version' => $data['version'] ?? '1.0.0',
            'description' => $data['description'] ?? '',
            'license' => $data['license'] ?? '',
            'author' => $author,
            'support' => $data['support'] ?? [],
            'gitUrl' => $authorGithub,
        ];
    }

    private static function logJsonError(string $path): void
    {
        SmartLogger::error('Failed to parse composer.json metadata')
            ->withPayload([
                'file' => $path,
                'json_error' => json_last_error_msg(),
            ])
            ->withPiiMasking()
            ->systemOnly()
            ->save();
    }

    private static function logReadError(string $path, \Throwable $e): void
    {
        SmartLogger::error('Failed to read composer.json metadata')
            ->withPayload([
                'file' => $path,
                'error' => $e->getMessage(),
            ])
            ->withPiiMasking()
            ->systemOnly()
            ->save();
    }
}
