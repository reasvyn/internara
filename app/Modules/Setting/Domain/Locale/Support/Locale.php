<?php

declare(strict_types=1);

namespace App\Modules\Setting\Domain\Locale\Support;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Lang;

final class Locale
{
    public const DEFAULT_LOCALE = 'en';

    public const SUPPORTED_LOCALES = [
        'en' => ['name' => 'English', 'native' => 'English', 'flag' => '🇬🇧'],
        'id' => ['name' => 'Indonesian', 'native' => 'Bahasa Indonesia', 'flag' => '🇮🇩'],
    ];

    public static function set(string $locale): bool
    {
        if (! self::isSupported($locale)) {
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
        $supported = self::all();

        if (session()->isStarted()) {
            $sessionLocale = session()->get('locale');
            if (is_string($sessionLocale) && isset($supported[$sessionLocale])) {
                return $sessionLocale;
            }
        }

        $locale = Cookie::get('locale');

        if (is_string($locale) && isset($supported[$locale])) {
            return $locale;
        }

        $cookieFromRequest = request()->cookie('locale');
        if (is_string($cookieFromRequest) && isset($supported[$cookieFromRequest])) {
            return $cookieFromRequest;
        }

        $stored = setting('default_locale');

        if (is_string($stored) && isset($supported[$stored])) {
            return $stored;
        }

        $config = config('app.locale', self::DEFAULT_LOCALE);

        return (is_string($config) && isset($supported[$config])) ? $config : self::DEFAULT_LOCALE;
    }

    public static function all(): array
    {
        $configured = config('app.supported_locales');
        if (is_array($configured) && $configured !== []) {
            return array_merge(self::SUPPORTED_LOCALES, $configured);
        }

        return self::SUPPORTED_LOCALES;
    }

    public static function keys(): array
    {
        return array_keys(self::all());
    }

    public static function isSupported(string $locale): bool
    {
        return isset(self::all()[$locale]);
    }

    public static function metadata(string $locale): ?array
    {
        return self::all()[$locale] ?? null;
    }

    public static function flag(string $locale): string
    {
        $meta = self::metadata($locale);
        if (isset($meta['flag']) && is_string($meta['flag']) && $meta['flag'] !== '') {
            return $meta['flag'];
        }

        $countryMap = [
            'en' => 'GB',
            'id' => 'ID',
            'ja' => 'JP',
            'ko' => 'KR',
            'zh' => 'CN',
            'da' => 'DK',
            'el' => 'GR',
            'cs' => 'CZ',
            'sv' => 'SE',
            'uk' => 'UA',
            'vi' => 'VN',
            'hi' => 'IN',
            'ar' => 'SA',
            'pt' => 'PT',
            'bn' => 'BD',
            'ur' => 'PK',
            'fa' => 'IR',
            'ms' => 'MY',
            'tl' => 'PH',
            'th' => 'TH',
            'nl' => 'NL',
            'pl' => 'PL',
            'tr' => 'TR',
            'it' => 'IT',
            'de' => 'DE',
            'es' => 'ES',
            'fr' => 'FR',
            'ru' => 'RU',
        ];

        $country = $countryMap[strtolower($locale)] ?? (strlen($locale) === 2 ? strtoupper($locale) : null);

        if ($country && strlen($country) === 2 && ctype_alpha($country)) {
            $c1 = ord(strtoupper($country[0]));
            $c2 = ord(strtoupper($country[1]));

            return mb_chr(0x1F1E6 + $c1 - 65, 'UTF-8').mb_chr(0x1F1E6 + $c2 - 65, 'UTF-8');
        }

        return '🌐';
    }

    public static function label(string $locale): string
    {
        $meta = self::metadata($locale);
        $nameKey = isset($meta['name']) ? strtolower((string) $meta['name']) : strtolower($locale);
        $translationKey = 'common.language.'.$nameKey;

        if (Lang::has($translationKey)) {
            return __($translationKey);
        }

        $localeKey = 'common.language.'.strtolower($locale);
        if (Lang::has($localeKey)) {
            return __($localeKey);
        }

        return $meta['native'] ?? $meta['name'] ?? strtoupper($locale);
    }
}
