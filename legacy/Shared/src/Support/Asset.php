<?php

declare(strict_types=1);

namespace Modules\Shared\Support;

/**
 * Utility class for handling Shared module assets.
 */
final class Asset
{
    /**
     * Resolves the absolute URL for a static asset residing in the Shared module.
     *
     * @param string $path The relative path to the asset.
     */
    public static function sharedUrl(string $path): string
    {
        return asset('modules/shared/'.ltrim($path, '/'));
    }
}
