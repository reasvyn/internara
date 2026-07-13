<?php

declare(strict_types=1);

namespace App\Settings\Observers;

use App\Settings\Models\Setting;
use Illuminate\Support\Facades\Cache;

final class SettingObserver
{
    public function created(Setting $setting): void
    {
        $this->invalidate($setting);
    }

    public function updated(Setting $setting): void
    {
        $this->invalidate($setting);
    }

    public function deleted(Setting $setting): void
    {
        $this->invalidate($setting);
    }

    private function invalidate(Setting $setting): void
    {
        $key = $setting->key;

        Cache::forget(config('cache-keys.settings_key') . $key);
        Cache::forget(config('cache-keys.settings_all'));

        if ($setting->group) {
            Cache::forget(config('cache-keys.settings_group') . $setting->group);
        }

        if (in_array($key, config('settings.theme_cache_keys', []), true)) {
            Cache::forget(config('cache-keys.theme_css_variables'));
            Cache::forget(config('cache-keys.brand_colors'));
        }
    }
}
