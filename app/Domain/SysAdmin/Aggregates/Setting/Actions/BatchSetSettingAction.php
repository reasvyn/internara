<?php

declare(strict_types=1);

namespace App\Domain\SysAdmin\Aggregates\Setting\Actions;

use App\Domain\Core\Actions\BaseAction;
use App\Domain\Core\Support\SmartLogger;
use App\Domain\SysAdmin\Aggregates\Setting\Enums\SettingGroup;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class BatchSetSettingAction extends BaseAction
{
    public function __construct(
        protected readonly SetSettingAction $setSettingAction,
    ) {}

    public function execute(array $settings): Collection
    {
        return DB::transaction(function () use ($settings) {
            $results = collect();
            $changed = [];

            foreach ($settings as $key => $config) {
                if (is_array($config) && isset($config['value'])) {
                    $value = $config['value'];
                    $group = $config['group'] ?? SettingGroup::default()->value;
                    $description = $config['description'] ?? null;
                    $type = $config['type'] ?? null;
                } else {
                    $value = $config;
                    $group = SettingGroup::default()->value;
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
        });
    }
}
