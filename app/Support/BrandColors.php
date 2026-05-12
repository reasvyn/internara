<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Centralized brand color management.
 *
 * Provides defaults, settings resolution, contrast computation,
 * CSS variable generation, and preset color palettes.
 */
final class BrandColors
{
    public const array DEFAULTS = [
        'primary' => '#0ea5e9',
        'secondary' => '#64748b',
        'accent' => '#f59e0b',
    ];

    public const string DEFAULT_BASE = '#ffffff';

    public const array PRESETS = [
        'sky' => [
            'label' => 'Sky',
            'colors' => ['primary' => '#0ea5e9', 'secondary' => '#64748b', 'accent' => '#f59e0b'],
        ],
        'emerald' => [
            'label' => 'Emerald',
            'colors' => ['primary' => '#059669', 'secondary' => '#6b7280', 'accent' => '#f97316'],
        ],
        'violet' => [
            'label' => 'Violet',
            'colors' => ['primary' => '#7c3aed', 'secondary' => '#71717a', 'accent' => '#ec4899'],
        ],
        'rose' => [
            'label' => 'Rose',
            'colors' => ['primary' => '#e11d48', 'secondary' => '#78716c', 'accent' => '#f59e0b'],
        ],
        'ocean' => [
            'label' => 'Ocean',
            'colors' => ['primary' => '#0891b2', 'secondary' => '#64748b', 'accent' => '#7c3aed'],
        ],
        'slate' => [
            'label' => 'Slate',
            'colors' => ['primary' => '#475569', 'secondary' => '#57534e', 'accent' => '#d97706'],
        ],
    ];

    public static function defaults(): array
    {
        return self::DEFAULTS;
    }

    public static function presets(): array
    {
        return self::PRESETS;
    }

    public static function presetKeys(): array
    {
        return array_keys(self::PRESETS);
    }

    public static function all(): array
    {
        $defaults = self::DEFAULTS;

        return [
            'primary' => Settings::get('primary_color', $defaults['primary']),
            'secondary' => Settings::get('secondary_color', $defaults['secondary']),
            'accent' => Settings::get('accent_color', $defaults['accent']),
        ];
    }

    public static function get(string $key): string
    {
        $colors = self::all();

        return $colors[$key] ?? self::DEFAULTS[$key] ?? '#000000';
    }

    public static function base(): string
    {
        return Settings::get('base_color', self::DEFAULT_BASE);
    }

    public static function relativeLuminance(string $hex): float
    {
        $rgb = self::hexToRgb($hex);

        return (0.299 * $rgb[0] + 0.587 * $rgb[1] + 0.114 * $rgb[2]) / 255;
    }

    public static function contrastColor(string $hex): string
    {
        return self::relativeLuminance($hex) > 0.5 ? '#000000' : '#ffffff';
    }

    public static function computeBaseShades(string $hex): array
    {
        $luminance = self::relativeLuminance($hex);

        // Light backgrounds use small steps; dark backgrounds use larger steps
        [$step1, $step2] = $luminance > 0.5 ? [3, 6] : [15, 25];

        [$r, $g, $b] = self::hexToRgb($hex);

        return [
            'base100' => $hex,
            'base200' => sprintf('#%02x%02x%02x', max(0, $r - $step1), max(0, $g - $step1), max(0, $b - $step1)),
            'base300' => sprintf('#%02x%02x%02x', max(0, $r - $step2), max(0, $g - $step2), max(0, $b - $step2)),
            'content' => $luminance > 0.5 ? '#1a1a1a' : '#f0f0f0',
        ];
    }

    public static function hexToRgb(string $hex): array
    {
        $hex = ltrim($hex, '#');

        return [
            hexdec(substr($hex, 0, 2)),
            hexdec(substr($hex, 2, 2)),
            hexdec(substr($hex, 4, 2)),
        ];
    }

    public static function lighten(string $hex, int $percent): string
    {
        $rgb = self::hexToRgb($hex);

        foreach ($rgb as &$channel) {
            $channel = min(255, $channel + (int) round((255 - $channel) * $percent / 100));
        }

        return sprintf('#%02x%02x%02x', $rgb[0], $rgb[1], $rgb[2]);
    }

    public static function cssVariables(): array
    {
        $colors = self::all();

        $light = [];
        $dark = [];

        // Base color shades
        $baseShades = self::computeBaseShades(self::base());
        $light['--color-base-100'] = $baseShades['base100'];
        $light['--color-base-200'] = $baseShades['base200'];
        $light['--color-base-300'] = $baseShades['base300'];
        $light['--color-base-content'] = $baseShades['content'];

        $map = [
            'primary' => ['--color-primary', '--p'],
            'secondary' => ['--color-secondary', '--s'],
            'accent' => ['--color-accent', '--a'],
        ];

        foreach ($map as $key => $variables) {
            $hex = $colors[$key];

            // Light theme
            foreach ($variables as $var) {
                $light[$var] = $hex;
            }
            $light['--color-'.$key.'-content'] = self::contrastColor($hex);
            $light['--'.$key[0].'c'] = self::contrastColor($hex);

            // Dark theme — lighten so colors are visible on dark backgrounds
            $lightened = self::lighten($hex, 40);
            foreach ($variables as $var) {
                $dark[$var] = $lightened;
            }
            $dark['--color-'.$key.'-content'] = '#ffffff';
            $dark['--'.$key[0].'c'] = '#ffffff';

            // Legacy brand variables
            $light['--brand-'.$key] = $hex;
            $dark['--brand-'.$key] = $lightened;
        }

        // Dark mode base colors stay near-black for readability
        $dark['--color-base-100'] = '#1f1f1f';
        $dark['--color-base-200'] = '#141414';
        $dark['--color-base-300'] = '#0a0a0a';
        $dark['--color-base-content'] = '#e5e5e5';

        return compact('light', 'dark');
    }

    public static function isValid(string $hex): bool
    {
        return (bool) preg_match('/^#[0-9A-Fa-f]{6}$/', $hex);
    }
}
