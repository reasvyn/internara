<?php

declare(strict_types=1);

namespace App\Domain\Settings\Actions;

use App\Domain\Core\Actions\BaseAction;
use App\Domain\Core\Support\SmartLogger;
use Illuminate\Support\Collection;

class BatchSetSettingAction extends BaseAction
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
            SmartLogger::info('Settings updated via batch')
                ->withPayload([
                    'count' => count($changed),
                    'groups' => $groups->toArray(),
                ])
                ->systemOnly()
                ->save();
        }

        return $results;
    }
}
