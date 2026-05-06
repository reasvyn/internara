<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;

final class Locale
{
    public const DEFAULT_LOCALE = 'id';

    public const SUPPORTED_LOCALES = [
        'en' => ['name' => 'English', 'native' => 'English', 'icon' => 'us'],
        'id' => ['name' => 'Indonesian', 'native' => 'Bahasa Indonesia', 'icon' => 'id'],
    ];

    public static function set(string $locale): bool
    {
        if (! isset(self::SUPPORTED_LOCALES[$locale])) {
            return false;
        }

        Session::put('locale', $locale);
        App::setLocale($locale);

        return true;
    }

    public static function current(): string
    {
        $locale = Session::get('locale', config('app.locale', self::DEFAULT_LOCALE));

        return isset(self::SUPPORTED_LOCALES[$locale]) ? $locale : self::DEFAULT_LOCALE;
    }

    public static function all(): array
    {
        return self::SUPPORTED_LOCALES;
    }

    public static function keys(): array
    {
        return array_keys(self::SUPPORTED_LOCALES);
    }

    public static function isSupported(string $locale): bool
    {
        return isset(self::SUPPORTED_LOCALES[$locale]);
    }

    public static function metadata(string $locale): ?array
    {
        return self::SUPPORTED_LOCALES[$locale] ?? null;
    }
}
