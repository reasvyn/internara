<?php

declare(strict_types=1);

namespace Modules\Shared\Support;

use Illuminate\Support\Str;

/**
 * Utility class for normalizing and formatting technical strings.
 *
 * Provides standardized methods for path and namespace manipulation, ensuring
 * consistency across different operating systems and architectural layers.
 */
final class Formatter
{
    /**
     * Formats a numerical value into the standard Indonesian Rupiah (IDR) currency format.
     *
     * Ensures consistent financial representation across the system using the
     * Rp symbol and dot-comma decimal separators.
     */
    public static function currency(int|float $value): string
    {
        return 'Rp ' . number_format($value, 0, ',', '.');
    }

    /**
     * Formats a date string or Carbon instance into a long Indonesian narrative format.
     *
     * Adheres to the localization mandate by translating month names into
     * Indonesian. Example: 2026-02-10 -> "10 Februari 2026".
     */
    public static function date(mixed $date, string $format = 'd F Y'): string
    {
        return \Illuminate\Support\Carbon::parse($date)->translatedFormat($format);
    }

    /**
     * Normalizes an Indonesian phone number into the international E.164 standard (+62).
     *
     * This normalization is required for unified database storage and ensuring
     * compatibility with notification gateways (SMS/WhatsApp).
     */
    public static function phone(string $phone): string
    {
        $cleaned = preg_replace('/[^0-9]/', '', $phone);

        if (str_starts_with($cleaned, '0')) {
            $cleaned = '62' . substr($cleaned, 1);
        }

        if (!str_starts_with($cleaned, '62')) {
            $cleaned = '62' . $cleaned;
        }

        return '+' . $cleaned;
    }

    /**
     * Joins multiple path segments and normalizes separators.
     *
     * Ensures OS-agnostic path resolution by converting backslashes to
     * forward slashes and removing redundant segment delimiters.
     */
    public static function path(?string ...$paths): string
    {
        $joined = implode('/', array_filter($paths));
        $normalized = Str::replace(['\\', '//'], '/', $joined);

        while (str_contains($normalized, '//')) {
            $normalized = Str::replace('//', '/', $normalized);
        }

        return trim($normalized, '/');
    }

    /**
     * Joins multiple namespace segments and normalizes delimiters.
     *
     * Corrects malformed namespace strings by enforcing single backslashes
     * and removing accidental forward slashes from the result.
     */
    public static function namespace(?string ...$parts): string
    {
        $joined = implode('\\', array_filter($parts));
        $normalized = Str::replace(['/', '\\\\'], '\\', $joined);

        while (str_contains($normalized, '\\\\')) {
            $normalized = Str::replace('\\\\', '\\', $normalized);
        }

        return trim($normalized, '\\');
    }
}
