<?php

declare(strict_types=1);

namespace App\Actions\Setting;

use App\Models\Setting;
use App\Support\Settings;
use Illuminate\Support\Collection;

/**
 * Stateless Action to set or update system settings.
 *
 * S1 - Secure: Centralized control over system configurations.
 * S2 - Sustain: Automatic cache invalidation with typed value storage.
 */
class SetSettingAction
{
    /**
     * Execute the setting update.
     *
     * The value's PHP type is automatically detected and stored with the
     * correct database type via SettingValueCast.
     */
    public function execute(string $key, mixed $value, ?string $group = 'general', ?string $description = null): Setting
    {
        $setting = Setting::updateOrCreate(
            ['key' => $key],
            [
                'value' => $value,
                'group' => $group,
                'description' => $description,
            ],
        );

        Settings::forget($key, $setting->group);

        return $setting;
    }

    /**
     * Set multiple settings in a single operation.
     *
     * @param array<string, mixed> $settings Key-value pairs, optionally with
     *                                       metadata: ['key' => 'value'] or ['key' => ['value' => 'x', 'group' => 'y']]
     *
     * @return Collection<int, Setting>
     */
    public function executeBatch(array $settings): Collection
    {
        $results = collect();

        foreach ($settings as $key => $config) {
            if (is_array($config) && isset($config['value'])) {
                $value = $config['value'];
                $group = $config['group'] ?? 'general';
                $description = $config['description'] ?? null;
            } else {
                $value = $config;
                $group = 'general';
                $description = null;
            }

            $results->push($this->execute($key, $value, $group, $description));
        }

        return $results;
    }
}
