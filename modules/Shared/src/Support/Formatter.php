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
     * Joins and normalizes multiple path segments.
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
     * Joins and normalizes multiple namespace segments.
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
