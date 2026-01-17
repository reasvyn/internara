<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Log;

/**
 * This file provides a global fallback for the setting() helper function.
 *
 * It defines a safe, non-functional version of setting() ONLY if the Setting
 * module is disabled or missing.
 */
if (! function_exists('setting')) {
    /**
     * Check if the Setting module is enabled by reading the statuses file directly.
     * This is necessary because this file is loaded early via composer autoload.
     */
    $isSettingModuleActive = (function () {
        $statusPath = base_path('modules_statuses.json');

        if (! file_exists($statusPath)) {
            return false;
        }

        $statuses = json_decode(file_get_contents($statusPath), true);

        return isset($statuses['Setting']) && $statuses['Setting'] === true;
    })();

    if (! $isSettingModuleActive) {
        /**
         * Fallback for the setting() helper function.
         */
        function setting(
            string|array|null $key = null,
            mixed $default = null,
            bool $skipCache = false,
        ): mixed {
            static $hasLogged = false;

            if (! $hasLogged && ! app()->runningInConsole()) {
                try {
                    Log::warning(
                        'The setting() helper was called, but the Setting module is disabled or missing. A fallback was used.',
                    );
                    $hasLogged = true;
                } catch (\Throwable) {
                    // Log might not be available yet
                }
            }

            if (is_array($key)) {
                return false;
            }

            if ($key === null) {
                return null;
            }

            return $default;
        }
    }
}
