<?php

declare(strict_types=1);

use Modules\Setting\Facades\Setting;

if (! function_exists('setting')) {
    /**
     * Get or set application settings.
     *
     * If $key is null, it returns the Setting service instance.
     * If $key is an array, it sets settings based on the key-value pairs.
     * If $key is a string, it retrieves the setting value.
     */
    function setting(
        string|array|null $key = null,
        mixed $default = null,
        bool $skipCache = false,
    ): mixed {
        if ($key === null) {
            return Setting::getFacadeRoot();
        }

        if (is_array($key) && \Illuminate\Support\Arr::isAssoc($key)) {
            return Setting::setValue($key);
        }

        return Setting::getValue($key, $default, $skipCache);
    }
}
