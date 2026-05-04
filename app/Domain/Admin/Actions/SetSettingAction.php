<?php

declare(strict_types=1);

namespace App\Domain\Admin\Actions;

use App\Domain\Core\Models\Setting;
use App\Domain\Core\Support\Settings;
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
     * correct database type via SettingValueCast, unless $type is explicitly provided.
     */
    public function execute(
        string $key,
        mixed $value,
        ?string $group = 'general',
        ?string $description = null,
        ?string $type = null,
    ): Setting {
        $detectedType = $type ?? $this->detectType($value);

        $setting = Setting::updateOrCreate(
            ['key' => $key],
            [
                'value' => $value,
                'type' => $detectedType,
                'group' => $group,
                'description' => $description,
            ],
        );

        Settings::forget($key, $setting->group);

        return $setting;
    }

    /**
     * Detect the PHP type of a value for storage.
     */
    protected function detectType(mixed $value): string
    {
        return match (true) {
            is_bool($value) => 'boolean',
            is_int($value) => 'integer',
            is_float($value) => 'float',
            is_array($value) => 'json',
            default => 'string',
        };
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
                $type = $config['type'] ?? null;
            } else {
                $value = $config;
                $group = 'general';
                $description = null;
                $type = null;
            }

            $results->push($this->execute($key, $value, $group, $description, $type));
        }

        return $results;
    }
}
