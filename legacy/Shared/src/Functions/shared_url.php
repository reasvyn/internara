<?php

declare(strict_types=1);

use Modules\Shared\Support\Asset;

if (! function_exists('shared_url')) {
    /**
     * Global wrapper for Modules\Shared\Support\Asset::sharedUrl.
     */
    function shared_url(string $path): string
    {
        return Asset::sharedUrl($path);
    }
}
