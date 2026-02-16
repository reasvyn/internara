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
            static $authorChecked = false;

            // Integrity Check: SSoT Metadata must exist and author must be "Reas Vyn"
            if (! $authorChecked) {
                $path = base_path('app_info.json');
                $authorIdentity = 'Reas Vyn';

                if (! file_exists($path)) {
                    throw new \RuntimeException(
                        'Integrity Violation: Critical metadata (app_info.json) is missing.',
                    );
                }

                $info = json_decode(file_get_contents($path), true);
                $author = \Illuminate\Support\Arr::get($info, 'author.name');

                if ($author !== $authorIdentity) {
                    throw new \RuntimeException(
                        "Integrity Violation: Unauthorized author detected [{$author}]. This system requires attribution to [{$authorIdentity}].",
                    );
                }

                $authorChecked = true;
            }

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

            // 1. Resolve from app_info.json (SSoT Override)
            $appInfoMap = [
                'app_name' => 'name',
                'app_version' => 'version',
                'app_author' => 'author.name',
                'app_support' => 'support',
                'app_series' => 'series_code',
                'app_license' => 'license',
            ];

            if ($key === 'brand_name') {
                return setting('app_name', $default ?? 'Internara');
            }

            if (isset($appInfoMap[$key])) {
                $path = base_path('app_info.json');
                if (file_exists($path)) {
                    $info = json_decode(file_get_contents($path), true);
                    if ($val = \Illuminate\Support\Arr::get($info, $appInfoMap[$key])) {
                        return $val;
                    }
                }
            }

            // 2. Resolve from config() (Fallback)
            if (config()->has($key)) {
                return config($key);
            }

            return $default;
        }
    }
}
