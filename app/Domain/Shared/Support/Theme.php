<?php

declare(strict_types=1);

namespace App\Domain\Shared\Support;

use App\Domain\Settings\Support\Color;
use App\Domain\Settings\Support\Settings;
use Illuminate\Support\Facades\Cache;

final class Theme
{
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

    public static function presetKeys(): array
    {
        return array_keys(self::presets());
    }

    public static function all(): array
    {
        $defaults = self::defaults();

        return [
            'primary' => Settings::get('primary_color', $defaults['primary']),
            'secondary' => Settings::get('secondary_color', $defaults['secondary']),
            'accent' => Settings::get('accent_color', $defaults['accent']),
            'base' => self::base(),
            'content' => Color::computeBaseShades(self::base())['content'],
        ];
    }

    public static function get(string $key): string
    {
        $colors = self::all();
        $defaults = self::defaults();

        return $colors[$key] ?? $defaults[$key] ?? '#000000';
    }

    public static function base(): string
    {
        $defaults = self::defaults();

        return Settings::get('base_color', $defaults['base']);
    }

    public static function cssVariables(): array
    {
        return Cache::remember('theme.css_variables', 3600, function () {
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
            }

            return compact('light', 'dark');
        });
    }
}
