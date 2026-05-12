<?php

declare(strict_types=1);

namespace App\Actions\Admin;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class BatchSetSettingAction
{
    public function __construct(
        protected readonly SetSettingAction $setSettingAction,
    ) {}

    public function execute(array $settings): Collection
    {
        $results = collect();
        $changed = [];

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

            $setting = $this->setSettingAction->execute($key, $value, $group, $description, $type);

            if ($setting->wasRecentlyCreated || $setting->wasChanged()) {
                $changed[] = $key;
            }

            $results->push($setting);
        }

        if ($changed !== []) {
            $groups = $results->pluck('group')->unique()->filter()->values();
            Log::info('Settings updated via batch', [
                'count' => count($changed),
                'groups' => $groups->toArray(),
            ]);
        }

        return $results;
    }
}
