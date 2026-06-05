<?php

declare(strict_types=1);

namespace App\Core\Support;

final class Color
{
    public static function hexToRgb(string $hex): array
    {
        $hex = ltrim($hex, '#');

        return [
            hexdec(substr($hex, 0, 2)),
            hexdec(substr($hex, 2, 2)),
            hexdec(substr($hex, 4, 2)),
        ];
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

        $darkBase = $luminance > 0.5 ? self::darken($lightHex, 80) : $lightHex;

        return [
            'base100' => $darkBase,
            'base200' => self::darken($darkBase, 6),
            'base300' => self::darken($darkBase, 10),
            'content' => '#e5e5e5',
        ];
    }
}
