<?php

declare(strict_types=1);

namespace App\Settings\Support;

use App\Core\Services\AppInfo;
use App\Core\Services\SmartLogger;
use App\Settings\Branding\Data\BrandData;
use App\Settings\Models\Setting as SettingModel;
use App\Settings\Theme\Support\Theme;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Throwable;

final class Brand
{
    public static function clearCache(): void
    {
        Cache::forget(config('cache-keys.brand_colors'));
    }

    public static function name(): string
    {
        return self::resolveValue('name', AppInfo::name());
    }

    public static function title(): string
    {
        return self::resolveValue('title', self::name());
    }

    public static function logo(): string
    {
        $default = Config::get('app.logo', asset('/brand/logo.png'));

        return self::resolveValue(['brand_logo', 'logo'], $default);
    }

    public static function favicon(): string
    {
        $default = Config::get('app.favicon', asset('/brand/favicon.ico'));

        return self::resolveValue('favicon', $default);
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
        $data = self::resolve();
        $normalized = str_replace('.', '_', $key);

        return match ($normalized) {
            'name' => $data->name,
            'title', 'site_title' => $data->title,
            'logo' => $data->logo,
            'favicon' => $data->favicon,
            'colors' => $data->colors,
            'version' => $data->version,
            'author_name' => $data->authorName,
            'author_email' => $data->authorEmail,
            'description' => $data->description,
            'license' => $data->license,
            'gitUrl' => $data->gitUrl,
            'tagline' => __('common.app_tagline'),
            default => $default,
        };
    }

    private static function resolveValue(string|array $key, string $fallback): string
    {
        $value = self::safe(function () use ($key) {
            $query = SettingModel::query();

            if (is_array($key)) {
                $query->whereIn('key', $key);
            } else {
                $query->where('key', $key);
            }

            return $query->value('value');
        }, null);

        return is_string($value) && $value !== '' ? $value : $fallback;
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
