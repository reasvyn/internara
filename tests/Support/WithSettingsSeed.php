<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Settings\Models\Setting;

trait WithSettingsSeed
{
    protected function seedSetting(
        string $key,
        mixed $value,
        string $group = 'setup',
        ?string $type = null,
    ): Setting {
        $type ??= match (true) {
            is_bool($value) => 'boolean',
            is_int($value) => 'integer',
            is_float($value) => 'float',
            is_array($value) => 'json',
            default => 'string',
        };

        $setting = Setting::updateOrCreate(
            ['key' => $key],
            ['value' => $value, 'group' => $group, 'type' => $type],
        );

        return $setting;
    }

    protected function seedSettings(array $settings): void
    {
        foreach ($settings as $key => $config) {
            $value = is_array($config) ? $config['value'] ?? null : $config;
            $group = is_array($config) ? $config['group'] ?? 'setup' : 'setup';
            $type = is_array($config) ? $config['type'] ?? null : null;

            $this->seedSetting($key, $value, $group, $type);
        }
    }
}
