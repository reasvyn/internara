<?php

declare(strict_types=1);

namespace App\Actions\Setting;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

/**
 * Stateless Action to set or update a system setting.
 * 
 * S1 - Secure: Centralized control over system configurations.
 * S2 - Sustain: Automatic cache invalidation.
 */
class SetSettingAction
{
    /**
     * Execute the setting update.
     */
    public function execute(string $key, mixed $value, ?string $type = 'string', ?string $group = 'general', ?string $description = null): Setting
    {
        $setting = Setting::updateOrCreate(
            ['key' => $key],
            [
                'value' => (string) $value,
                'type' => $type,
                'group' => $group,
                'description' => $description,
            ]
        );

        // Invalidate settings cache
        Cache::forget("settings.{$key}");
        Cache::forget('settings.all');

        return $setting;
    }
}
