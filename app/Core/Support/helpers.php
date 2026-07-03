<?php

declare(strict_types=1);

use App\Core\Services\AppInfo;

if (! function_exists('app_info')) {
    /**
     * Get application metadata from Composer (SSoT) and config.
     *
     * S2 - Sustain: Centralized access to Composer metadata.
     *
     * @param string|null $key Metadata key (name, version, author, etc.)
     * @param mixed $default Default value when key is not found
     */
    function app_info(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return AppInfo::all();
        }

        return AppInfo::get($key, $default);
    }
}
