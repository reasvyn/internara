<?php

declare(strict_types=1);

namespace App\Settings\Actions;

use App\Core\Actions\BaseAction;
use App\Settings\Enums\SettingGroup;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class BatchSetSettingAction extends BaseAction
{
    public function __construct(protected readonly SetSettingAction $setSettingAction) {}

    public function execute(array $settings): Collection
    {
        return DB::transaction(function () use ($settings) {
            $results = collect();

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

                $setting = $this->setSettingAction->execute(
                    $key,
                    $value,
                    $group,
                    $description,
                    $type,
                );
                $results->push($setting);
            }

            return $results;
        });
    }
}
