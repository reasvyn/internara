<?php

declare(strict_types=1);

namespace App\Modules\Setting\Domain\Locale\Support;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cookie;

final class Locale
{
    public const DEFAULT_LOCALE = 'en';

    public const SUPPORTED_LOCALES = [
        'en' => ['name' => 'English', 'native' => 'English'],
        'id' => ['name' => 'Indonesian', 'native' => 'Bahasa Indonesia'],
    ];

    public static function set(string $locale): bool
    {
        if (! isset(self::SUPPORTED_LOCALES[$locale])) {
            return false;
        }

        Cookie::queue(Cookie::forever('locale', $locale));

        if (session()->isStarted()) {
            session()->put('locale', $locale);
        }

        App::setLocale($locale);

        return true;
    }

    public static function current(): string
    {
        if (session()->isStarted()) {
            $sessionLocale = session()->get('locale');
            if (is_string($sessionLocale) && isset(self::SUPPORTED_LOCALES[$sessionLocale])) {
                return $sessionLocale;
            }
        }

        $locale = Cookie::get('locale');

        if (is_string($locale) && isset(self::SUPPORTED_LOCALES[$locale])) {
            return $locale;
        }

        $cookieFromRequest = request()->cookie('locale');
        if (is_string($cookieFromRequest) && isset(self::SUPPORTED_LOCALES[$cookieFromRequest])) {
            return $cookieFromRequest;
        }

        $stored = setting('default_locale');

        if (is_string($stored) && isset(self::SUPPORTED_LOCALES[$stored])) {
            return $stored;
        }

        $config = config('app.locale', self::DEFAULT_LOCALE);

        return (is_string($config) && isset(self::SUPPORTED_LOCALES[$config])) ? $config : self::DEFAULT_LOCALE;
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
