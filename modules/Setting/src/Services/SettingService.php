<?php

declare(strict_types=1);

namespace Modules\Setting\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Modules\Core\Services\Contracts\MetadataService;
use Modules\Setting\Models\Setting;
use Modules\Shared\Services\EloquentQuery;

/**
 * Class SettingService
 *
 * Provides a cached, centralized API for managing application-wide dynamic settings.
 * This service acts as the orchestration layer for database-backed configurations,
 * config-based fallbacks, and SSoT metadata resolution.
 */
class SettingService extends EloquentQuery implements Contracts\SettingService
{
    /**
     * Cache key constants.
     */
    protected const CACHE_PREFIX = 'settings.';

    /**
     * Create a new SettingService instance.
     */
    public function __construct(Setting $model, protected MetadataService $metadataService)
    {
        $this->setModel($model);
        $this->setSearchable(['key', 'group']);
        $this->setSortable(['key', 'group']);

        $this->metadataService->verifyIntegrity();
    }

    /**
     * {@inheritDoc}
     */
    public function getValue(
        string|array $key,
        mixed $default = null,
        bool $skipCache = false,
    ): mixed {
        if (is_array($key)) {
            $results = [];
            foreach ($key as $k) {
                $results[$k] = $this->getValue($k, $default, $skipCache);
            }

            return $results;
        }

        // 1. Check App Info SSoT (Override/Primary Source)
        if ($infoValue = $this->resolveAppInfoValue($key)) {
            return $infoValue;
        }

        // 2. Check Database (Cached)
        $dbValue = $this->remember(
            self::CACHE_PREFIX.$key,
            now()->addDay(),
            function () use ($key) {
                $setting = $this->model->find($key);

                return $setting?->value;
            },
            $skipCache,
        );

        if ($dbValue !== null) {
            return $dbValue;
        }

        // 3. Check Laravel Config (Fallback)
        if (config()->has($key)) {
            return config($key);
        }

        // 4. Final Fallback to Default Parameter
        return $default;
    }

    /**
     * Map setting keys to MetadataService fields for SSoT resolution.
     */
    protected function resolveAppInfoValue(string $key): mixed
    {
        $map = [
            'app_name' => 'name',
            'app_version' => 'version',
            'app_author' => 'author.name',
            'app_support' => 'support',
            'app_series' => 'series_code',
            'app_license' => 'license',
        ];

        if (! isset($map[$key])) {
            return null;
        }

        return $this->metadataService->get($map[$key]);
    }

    /**
     * {@inheritDoc}
     */
    public function setValue(
        string|array $key,
        mixed $value = null,
        array $extraAttributes = [],
    ): bool {
        if (is_array($key)) {
            $success = true;
            foreach ($key as $k => $v) {
                $currentValue = is_array($v) ? $v['value'] ?? null : $v;
                $attributes = is_array($v) ? array_diff_key($v, ['value' => null]) : [];
                if (! $this->setValue($k, $currentValue, $attributes)) {
                    $success = false;
                }
            }

            return $success;
        }

        $setting = $this->model->updateOrCreate(
            ['key' => $key],
            array_merge(['value' => $value], $extraAttributes),
        );

        $this->clearCache($key, $setting->group);

        return (bool) $setting;
    }

    /**
     * {@inheritDoc}
     */
    public function group(string $name, bool $skipCache = false): Collection
    {
        return $this->remember(
            self::CACHE_PREFIX.'group.'.$name,
            now()->addDay(),
            fn () => $this->model->group($name)->get(),
            $skipCache,
        );
    }

    /**
     * {@inheritDoc}
     */
    public function set(string|array $key, mixed $value = null, array $extraAttributes = []): bool
    {
        return $this->setValue($key, $value, $extraAttributes);
    }

    /**
     * Clear the cache for a specific setting and its related groups.
     */
    protected function clearCache(string $key, ?string $group = null): void
    {
        Cache::forget(self::CACHE_PREFIX.$key);

        if ($group) {
            Cache::forget(self::CACHE_PREFIX.'group.'.$group);
        }

        Cache::forget(self::CACHE_PREFIX.'all');
    }
}
