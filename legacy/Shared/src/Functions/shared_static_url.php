<?php

declare(strict_types=1);

if (! function_exists('shared_static_url')) {
    /**
     * Resolves the absolute URL for a static asset residing in the Shared module.
     *
     * @param string $path The relative path to the asset.
     */
    function shared_static_url(string $path): string
    {
        return asset('modules/shared/'.ltrim($path, '/'));
    }
}
