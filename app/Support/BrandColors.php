<?php

declare(strict_types=1);

namespace App\Support;

final class BrandColors
{
    public const array DEFAULTS = [
        'primary' => '#0ea5e9',
        'secondary' => '#64748b',
        'accent' => '#f59e0b',
        'base' => '#ffffff',
        'content' => '#1a1a1a',
    ];

    public const array PRESETS = [
        'sky' => [
            'label' => 'Sky',
            'colors' => [
                'primary' => '#0ea5e9',
                'secondary' => '#64748b',
                'accent' => '#f59e0b',
                'base' => '#ffffff',
                'content' => '#1a1a1a',
            ],
        ],
        'emerald' => [
            'label' => 'Emerald',
            'colors' => [
                'primary' => '#059669',
                'secondary' => '#6b7280',
                'accent' => '#f97316',
                'base' => '#ffffff',
                'content' => '#1a1a1a',
            ],
        ],
        'violet' => [
            'label' => 'Violet',
            'colors' => [
                'primary' => '#7c3aed',
                'secondary' => '#71717a',
                'accent' => '#ec4899',
                'base' => '#ffffff',
                'content' => '#1a1a1a',
            ],
        ],
        'rose' => [
            'label' => 'Rose',
            'colors' => [
                'primary' => '#e11d48',
                'secondary' => '#78716c',
                'accent' => '#f59e0b',
                'base' => '#ffffff',
                'content' => '#1a1a1a',
            ],
        ],
        'ocean' => [
            'label' => 'Ocean',
            'colors' => [
                'primary' => '#0891b2',
                'secondary' => '#64748b',
                'accent' => '#7c3aed',
                'base' => '#ffffff',
                'content' => '#1a1a1a',
            ],
        ],
        'slate' => [
            'label' => 'Slate',
            'colors' => [
                'primary' => '#475569',
                'secondary' => '#57534e',
                'accent' => '#d97706',
                'base' => '#ffffff',
                'content' => '#1a1a1a',
            ],
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
        return [
            'primary' => Settings::get('primary_color', self::DEFAULTS['primary']),
            'secondary' => Settings::get('secondary_color', self::DEFAULTS['secondary']),
            'accent' => Settings::get('accent_color', self::DEFAULTS['accent']),
            'base' => self::base(),
            'content' => self::computeBaseShades(self::base())['content'],
        ];
    }

    public static function get(string $key): string
    {
        $colors = self::all();

        return $colors[$key] ?? self::DEFAULTS[$key] ?? '#000000';
    }

    public static function base(): string
    {
        return Settings::get('base_color', self::DEFAULTS['base']);
    }

    public static function relativeLuminance(string $hex): float
    {
        $rgb = self::hexToRgb($hex);

        return (0.299 * $rgb[0] + 0.587 * $rgb[1] + 0.114 * $rgb[2]) / 255;
    }

    public static function contrastColor(string $hex): string
    {
        return self::relativeLuminance($hex) > 0.5 ? '#1a1a1a' : '#f0f0f0';
    }

    public static function computeBaseShades(string $hex): array
    {
        $luminance = self::relativeLuminance($hex);

        if ($luminance > 0.5) {
            return [
                'base100' => $hex,
                'base200' => self::darken($hex, 3),
                'base300' => self::darken($hex, 6),
                'content' => '#1a1a1a',
            ];
        }

        return [
            'base100' => $hex,
            'base200' => self::lighten($hex, 10),
            'base300' => self::lighten($hex, 20),
            'content' => '#f0f0f0',
        ];
    }

    public static function computeDarkShades(string $lightHex): array
    {
        $luminance = self::relativeLuminance($lightHex);

        if ($luminance > 0.5) {
            $darkBase = self::darken($lightHex, 80);
        } else {
            $darkBase = $lightHex;
        }

        return [
            'base100' => $darkBase,
            'base200' => self::darken($darkBase, 6),
            'base300' => self::darken($darkBase, 10),
            'content' => '#e5e5e5',
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

    public static function darken(string $hex, int $percent): string
    {
        $rgb = self::hexToRgb($hex);

        foreach ($rgb as &$channel) {
            $channel = max(0, $channel - (int) round($channel * $percent / 100));
        }

        return sprintf('#%02x%02x%02x', $rgb[0], $rgb[1], $rgb[2]);
    }

    public static function cssVariables(): array
    {
        $colors = self::all();

        $light = [];
        $dark = [];

        $baseShades = self::computeBaseShades(self::base());
        $light['--color-base-100'] = $baseShades['base100'];
        $light['--color-base-200'] = $baseShades['base200'];
        $light['--color-base-300'] = $baseShades['base300'];
        $light['--color-base-content'] = $baseShades['content'];

        $darkShades = self::computeDarkShades(self::base());
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
            $light['--color-'.$key.'-content'] = self::contrastColor($hex);
            $light['--'.$key[0].'c'] = self::contrastColor($hex);

            $lightened = self::lighten($hex, 40);
            foreach ($variables as $var) {
                $dark[$var] = $lightened;
            }
            $dark['--color-'.$key.'-content'] = '#ffffff';
            $dark['--'.$key[0].'c'] = '#ffffff';

            $light['--brand-'.$key] = $hex;
            $dark['--brand-'.$key] = $lightened;
        }

        return compact('light', 'dark');
    }

    public static function isValid(string $hex): bool
    {
        return (bool) preg_match('/^#[0-9A-Fa-f]{6}$/', $hex);
    }
}
