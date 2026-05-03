<?php

declare(strict_types=1);

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

/**
 * This file provides a global fallback for the setting() helper function.
 *
 * It defines a safe, non-functional version of setting() ONLY if the Setting
 * module is disabled or missing.
 *
 * Each function has a single, clear purpose (Section 6 compliance).
 */
if (! function_exists('setting')) {
    /**
     * Check if the Setting module is enabled.
     */
    function isSettingModuleActive(): bool
    {
        $statusPath = dirname(__DIR__, 3).'/modules_statuses.json';

        if (! file_exists($statusPath)) {
            return false;
        }

        $statuses = json_decode(file_get_contents($statusPath), true);

        return isset($statuses['Setting']) && $statuses['Setting'] === true;
    }

    /**
     * Verify application metadata integrity.
     *
     * Ensures app_info.json exists and author is "Reas Vyn".
     * Throws RuntimeException on integrity violation.
     */
    function verifyMetadataIntegrity(): void
    {
        static $checked = false;

        if ($checked) {
            return;
        }

        $path = base_path('app_info.json');
        $authorIdentity = 'Reas Vyn';

        if (! file_exists($path)) {
            throw new RuntimeException(
                'Integrity Violation: Critical metadata (app_info.json) is missing.',
            );
        }

        $info = json_decode(file_get_contents($path), true);
        $author = Arr::get($info, 'author.name');

        if ($author !== $authorIdentity) {
            throw new RuntimeException(
                "Integrity Violation: Unauthorized author detected [{$author}]. This system requires attribution to [{$authorIdentity}].",
            );
        }

        $checked = true;
    }

    /**
     * Log a warning that Setting module is unavailable.
     *
     * Logs only once per request to avoid spam.
     */
    function logSettingUnavailable(): void
    {
        static $logged = false;

        if ($logged || app()->runningInConsole()) {
            return;
        }

        try {
            Log::warning(
                'The setting() helper was called, but the Setting module is disabled or missing. A fallback was used.',
            );
            $logged = true;
        } catch (Throwable) {
            // Log might not be available yet
        }
    }

    /**
     * Resolve a key from app_info.json SSoT.
     */
    function resolveFromAppInfo(string $key, mixed $default): mixed
    {
        $appInfoMap = [
            'app_name' => 'name',
            'app_version' => 'version',
            'app_author' => 'author.name',
            'app_support' => 'support',
            'app_license' => 'license',
        ];

        if ($key === 'brand_name') {
            return resolveFromAppInfo('app_name', $default ?? 'Internara');
        }

        if (! isset($appInfoMap[$key])) {
            return null;
        }

        $path = base_path('app_info.json');
        if (! file_exists($path)) {
            return null;
        }

        $info = json_decode(file_get_contents($path), true);
        $value = Arr::get($info, $appInfoMap[$key]);

        return $value ?? null;
    }

    /**
     * Fallback for the setting() helper function.
     *
     * Single orchestrator that calls single-purpose functions.
     */
    function setting(
        string|array|null $key = null,
        mixed $default = null,
        bool $skipCache = false,
    ): mixed {
        // Verify integrity
        verifyMetadataIntegrity();

        // Log availability warning
        logSettingUnavailable();

        // Handle array input
        if (is_array($key)) {
            return false;
        }

        // Handle null key
        if ($key === null) {
            return null;
        }

        // Try app_info.json first
        $value = resolveFromAppInfo($key, $default);
        if ($value !== null) {
            return $value;
        }

        // Fallback to config
        if (config()->has($key)) {
            return config($key);
        }

        return $default;
    }
}
