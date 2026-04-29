<?php

declare(strict_types=1);

namespace Modules\Setting\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\Setting\Services\Contracts\SettingService;

/**
 * @title Setting Facade
 *
 * @description Provides a convenient static interface for interacting with the application's SettingService.
 * This facade allows easy access to various setting management functionalities, including retrieving
 * setting values by key, updating existing settings, and organizing settings by groups. It serves
 * as a simplified entry point for managing application-wide configurations, abstracting away the
 * underlying service implementation.
 *
 * @see SettingService
 * @see \Modules\Setting\Services\SettingService
 *
 * @method static mixed getValue(string|array $key, mixed $default = null, bool $skipCached = false)
 * @method static bool setValue(string|array $key, mixed $value, array $extraAttributes = [])
 * @method static array<string, mixed> getValues(array $filters = [], bool $skipCached = false)
 * @method static \Illuminate\Support\Collection group(string $name)
 * @method static bool set(string|array $key, mixed $value = null, array $extraAttributes = [])
 * @method static \Illuminate\Database\Eloquent\Collection setGroup(\Illuminate\Database\Eloquent\Collection|\Modules\Setting\Models\Setting $settings)
 */
class Setting extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return SettingService::class;
    }
}
