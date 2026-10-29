<?php

declare(strict_types=1);

namespace App\Modules\Setting\Support;

use App\Modules\Core\Services\AppInfo;
use App\Modules\Core\Services\SmartLogger;
use App\Modules\Setting\Domain\Branding\Data\BrandData;
use App\Modules\Setting\Domain\Theme\Support\Theme;
use App\Modules\Setting\Services\Settings;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Throwable;

final class Brand
{
    /**
     * Per-request memo of resolved brand values.
     *
     * `brand()` is called 27 times across layouts and components, and each call
     * used to resolve the whole brand from the database. Memoising keeps the
     * resolution to one pass per request; writes reset it via clearCache().
     *
     * @var array<string, mixed>
     */
    private static array $memo = [];

    public static function clearCache(): void
    {
        self::$memo = [];
        Cache::forget(config('cache-keys.brand_colors'));
    }

    public static function name(): string
    {
        return self::$memo['name'] ??= self::resolveValue(['brand_name', 'name'], AppInfo::name());
    }

    public static function title(): string
    {
        return self::$memo['title'] ??= self::resolveValue(['site_title', 'title'], self::name());
    }

    public static function logo(): string
    {
        $default = Config::get('app.logo', asset('/brand/logo.png'));

        return self::$memo['logo'] ??= self::resolveValue(['brand_logo', 'logo'], $default);
    }

    public static function favicon(): string
    {
        $default = Config::get('app.favicon', asset('/brand/favicon.ico'));

        return self::$memo['favicon'] ??= self::resolveValue('favicon', $default);
    }

    public static function colors(): array
    {
        return Cache::remember(config('cache-keys.brand_colors'), 86400, function () {
            return self::safe(fn () => Theme::all(), Theme::defaults());
        });
    }

    public static function version(): string
    {
        return AppInfo::version();
    }

    public static function authorName(): string
    {
        return AppInfo::authorName();
    }

    public static function authorEmail(): string
    {
        return AppInfo::authorEmail();
    }

    public static function description(): string
    {
        return AppInfo::description();
    }

    public static function license(): string
    {
        return AppInfo::license();
    }

    public static function gitUrl(): string
    {
        return AppInfo::gitUrl();
    }

    public static function resolve(): BrandData
    {
        return new BrandData(
            name: self::name(),
            title: self::title(),
            logo: self::logo(),
            favicon: self::favicon(),
            colors: self::colors(),
            version: self::version(),
            authorName: self::authorName(),
            authorEmail: self::authorEmail(),
            description: self::description(),
            license: self::license(),
            gitUrl: self::gitUrl(),
        );
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $normalized = str_replace('.', '_', $key);

        // Dispatch per key: building the whole BrandData DTO for a single value
        // made every `brand('name')` resolve the logo, favicon and palette too.
        return match ($normalized) {
            'name' => self::name(),
            'title', 'site_title' => self::title(),
            'logo' => self::logo(),
            'favicon' => self::favicon(),
            'colors' => self::colors(),
            'version' => self::version(),
            'author_name' => self::authorName(),
            'author_email' => self::authorEmail(),
            'description' => self::description(),
            'license' => self::license(),
            'gitUrl' => self::gitUrl(),
            'tagline' => __('common.app_tagline'),
            default => $default,
        };
    }

    private static function resolveValue(string|array $key, string $fallback): string
    {
        // Read through the Settings service so the value comes from the cached
        // settings map instead of a fresh query per brand key.
        foreach ((array) $key as $candidate) {
            $value = self::safe(fn () => Settings::get($candidate), null);

            if (is_string($value) && $value !== '') {
                return $value;
            }
        }

        return $fallback;
    }

    private static function safe(callable $resolver, mixed $fallback = null): mixed
    {
        try {
            return $resolver();
        } catch (Throwable $e) {
            SmartLogger::warning('Brand resolution failed')
                ->withPayload([
                    'error' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ])
                ->withPiiMasking()
                ->systemOnly()
                ->save();

            return $fallback;
        }
    }
}
