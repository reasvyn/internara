<?php

declare(strict_types=1);

namespace App\Settings\Listeners;

use App\Settings\Events\SettingUpdated;
use Illuminate\Support\Facades\Cache;

final class InvalidateSettingsCache
{
    public function handle(SettingUpdated $event): void
    {
        $key = $event->setting->key;

        Cache::forget(config('cache-keys.settings_key').$key);
        Cache::forget(config('cache-keys.settings_all'));

        if ($event->setting->group) {
            Cache::forget(config('cache-keys.settings_group').$event->setting->group);
        }

        if (in_array($key, config('settings.theme_cache_keys', []), true)) {
            Cache::forget(config('cache-keys.theme_css_variables'));
            Cache::forget(config('cache-keys.brand_colors'));
        }
    }
}