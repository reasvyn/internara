<?php

declare(strict_types=1);

namespace App\Core\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;

final class AppInfo
{
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
        return Config::get('app.name', self::get('name', 'Laravel'));
    }

    public static function version(): string
    {
        return (string) Config::get('app.version', self::get('version', '1.0.0'));
    }

    public static function description(): string
    {
        return Config::get('app.description', self::get('description', ''));
    }

    public static function license(): string
    {
        return Config::get('app.license', self::get('license', 'MIT'));
    }

    public static function author(): array
    {
        return Config::get('app.author', self::get('author', ['name' => 'Reas Vyn']));
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
        return Config::get('app.git', self::get('gitUrl', 'https://github.com/reasvyn/internara'));
    }

    public static function clearCache(): void
    {
        self::$metadata = null;
        Cache::forget(self::cacheKey());
    }

    private static function cacheKey(): string
    {
        return config('cache-keys.appinfo_metadata');
    }

    private static function load(): array
    {
        return Cache::remember(self::cacheKey(), 86400, function () {
            return self::readFromComposer();
        });
    }

    private static function readFromComposer(): array
    {
        $path = base_path('composer.json');

        if (! File::exists($path)) {
            return self::defaults();
        }

        try {
            $rawContent = File::get($path);
            $data = json_decode($rawContent, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                self::logJsonError($path);

                return self::defaults();
            }

            return self::extractMetadata(is_array($data) ? $data : []);
        } catch (\Throwable $e) {
            self::logReadError($path, $e);

            return self::defaults();
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

    private static function defaults(): array
    {
        return [
            'name' => 'Laravel',
            'version' => '1.0.0',
            'description' => '',
            'license' => '',
            'author' => ['name' => 'Reas Vyn'],
            'support' => [],
            'gitUrl' => 'https://github.com/reasvyn/internara',
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
