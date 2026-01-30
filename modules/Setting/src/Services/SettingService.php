<?php

declare(strict_types=1);

namespace Modules\Setting\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Modules\Setting\Models\Setting;
use Modules\Shared\Services\EloquentQuery;

/**
 * Class SettingService
 *
 * Provides a cached, centralized API for managing application-wide dynamic settings.
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
    public function __construct(Setting $model)
    {
        $this->setModel($model);
        $this->setSearchable(['key', 'group']);
        $this->setSortable(['key', 'group']);
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

        return $this->remember(
            self::CACHE_PREFIX.$key,
            now()->addDay(),
            function () use ($key, $default) {
                $setting = $this->model->find($key);

                if (! $setting) {
                    return $default;
                }

                return $setting->value;
            },
            $skipCache,
        );
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
