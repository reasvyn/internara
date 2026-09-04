<?php

declare(strict_types=1);

namespace App\Modules\Settings\Domain\Theme\Support;

use App\Modules\Core\Support\Color;
use App\Modules\Settings\Services\Settings;
use Illuminate\Support\Facades\Cache;

final class Theme
{
    private static function getSetting(string $key, mixed $default = null): mixed
    {
        return Settings::get($key, $default);
    }

    public static function defaults(): array
    {
        $defaults = config('settings.colors.defaults');

        if (is_array($defaults)) {
            return $defaults;
        }

        return [
            'primary' => '#059669',
            'secondary' => '#6b7280',
            'accent' => '#f97316',
            'base' => '#ffffff',
            'content' => '#1a1a1a',
        ];
    }

    public static function presets(): array
    {
        return config('settings.colors.presets', []);
    }

    public static function all(): array
    {
        $defaults = self::defaults();

        return [
            'primary' => self::getSetting('primary_color', $defaults['primary']),
            'secondary' => self::getSetting('secondary_color', $defaults['secondary']),
            'accent' => self::getSetting('accent_color', $defaults['accent']),
            'base' => self::base(),
            'content' => Color::computeBaseShades(self::base())['content'],
        ];
    }

    public static function get(string $key): string
    {
        $colors = self::all();
        $defaults = self::defaults();

        return $colors[$key] ?? ($defaults[$key] ?? '#000000');
    }

    public static function base(): string
    {
        $defaults = self::defaults();

        return self::getSetting('base_color', $defaults['base']);
    }

    public static function cssVariables(): array
    {
        return Cache::remember(config('cache-keys.theme_css_variables'), 3600, function () {
            $colors = self::all();

            $light = [];
            $dark = [];

            $baseShades = Color::computeBaseShades(self::base());
            $light['--color-base-100'] = $baseShades['base100'];
            $light['--color-base-200'] = $baseShades['base200'];
            $light['--color-base-300'] = $baseShades['base300'];
            $light['--color-base-content'] = $baseShades['content'];

            $darkShades = Color::computeDarkShades(self::base());
            $dark['--color-base-100'] = $darkShades['base100'];
            $dark['--color-base-200'] = $darkShades['base200'];
            $dark['--color-base-300'] = $darkShades['base300'];
            $dark['--color-base-content'] = $darkShades['content'];

            $map = [
                'primary' => ['--color-primary', '--p'],
                'secondary' => ['--color-secondary', '--s'],
                'accent' => ['--color-accent', '--a'],
            ];

            foreach ($map as $key => $variables) {
                $hex = $colors[$key];

                foreach ($variables as $var) {
                    $light[$var] = $hex;
                }
                $light['--color-'.$key.'-content'] = Color::contrastColor($hex);
                $light['--'.$key[0].'c'] = Color::contrastColor($hex);

                $lightened = Color::lighten($hex, 40);
                foreach ($variables as $var) {
                    $dark[$var] = $lightened;
                }
                $dark['--color-'.$key.'-content'] = '#ffffff';
                $dark['--'.$key[0].'c'] = '#ffffff';

                $light['--brand-'.$key] = $hex;
                $dark['--brand-'.$key] = $lightened;

                // TallStackUI scale: --color-{key}-50 … --color-{key}-950
                // TallStackUI components use `bg-primary-500` / `ring-primary-600` etc.,
                // not the single `--color-primary` token. Without this scale the brand
                // color never reaches TallStackUI — hence the stuck indigo.
                foreach (Color::tailwindScale($hex) as $shade => $shadeHex) {
                    $light['--color-'.$key.'-'.$shade] = $shadeHex;
                    $light['--'.$key[0].'-'.$shade] = $shadeHex;
                }
                foreach (Color::tailwindScale($lightened) as $shade => $shadeHex) {
                    $dark['--color-'.$key.'-'.$shade] = $shadeHex;
                    $dark['--'.$key[0].'-'.$shade] = $shadeHex;
                }
            }

            return compact('light', 'dark');
        });
    }
}
