<?php

declare(strict_types=1);

namespace App\Modules\Core\Support;

final class Color
{
    private const LUMINANCE_RED = 0.299;

    private const LUMINANCE_GREEN = 0.587;

    private const LUMINANCE_BLUE = 0.114;

    private const CONTENT_DARK = '#1a1a1a';

    private const CONTENT_LIGHT = '#f0f0f0';

    private const CONTENT_DARK_MODE = '#e5e5e5';

    private const DARK_BASE_100 = '#262626';

    private const DARK_BASE_200 = '#171717';

    private const DARK_BASE_300 = '#0a0a0a';

    private const LIGHT_SHADE_200_PERCENT = 10;

    private const LIGHT_SHADE_300_PERCENT = 20;

    private const SHADE_200_PERCENT = 3;

    private const SHADE_300_PERCENT = 6;

    public static function hexToRgb(string $hex): array
    {
        $hex = ltrim($hex, '#');

        return [hexdec(substr($hex, 0, 2)), hexdec(substr($hex, 2, 2)), hexdec(substr($hex, 4, 2))];
    }

    public static function rgbToHex(int $r, int $g, int $b): string
    {
        return sprintf('#%02x%02x%02x', $r, $g, $b);
    }

    public static function relativeLuminance(string $hex): float
    {
        $rgb = self::hexToRgb($hex);

        return (self::LUMINANCE_RED * $rgb[0] + self::LUMINANCE_GREEN * $rgb[1] + self::LUMINANCE_BLUE * $rgb[2]) / 255;
    }

    public static function contrastColor(string $hex): string
    {
        return self::relativeLuminance($hex) > 0.5 ? self::CONTENT_DARK : self::CONTENT_LIGHT;
    }

    public static function lighten(string $hex, int $percent): string
    {
        return self::adjustBrightness($hex, $percent, true);
    }

    public static function darken(string $hex, int $percent): string
    {
        return self::adjustBrightness($hex, $percent, false);
    }

    public static function isValid(string $hex): bool
    {
        return (bool) preg_match('/^#[0-9A-Fa-f]{6}$/', $hex);
    }

    public static function computeBaseShades(string $hex): array
    {
        $luminance = self::relativeLuminance($hex);

        if ($luminance > 0.5) {
            return [
                'base100' => $hex,
                'base200' => self::darken($hex, self::SHADE_200_PERCENT),
                'base300' => self::darken($hex, self::SHADE_300_PERCENT),
                'content' => self::CONTENT_DARK,
            ];
        }

        return [
            'base100' => $hex,
            'base200' => self::lighten($hex, self::LIGHT_SHADE_200_PERCENT),
            'base300' => self::lighten($hex, self::LIGHT_SHADE_300_PERCENT),
            'content' => self::CONTENT_LIGHT,
        ];
    }

    public static function computeDarkShades(string $lightHex): array
    {
        return [
            'base100' => self::DARK_BASE_100,
            'base200' => self::DARK_BASE_200,
            'base300' => self::DARK_BASE_300,
            'content' => self::CONTENT_DARK_MODE,
        ];
    }

    private static function adjustBrightness(string $hex, int $percent, bool $lighten): string
    {
        $rgb = self::hexToRgb($hex);

        foreach ($rgb as &$channel) {
            if ($lighten) {
                $channel = min(255, $channel + (int) round(((255 - $channel) * $percent) / 100));
            } else {
                $channel = max(0, $channel - (int) round(($channel * $percent) / 100));
            }
        }

        return self::rgbToHex($rgb[0], $rgb[1], $rgb[2]);
    }
}
