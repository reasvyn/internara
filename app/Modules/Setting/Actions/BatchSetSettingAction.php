<?php

declare(strict_types=1);

namespace App\Modules\Setting\Actions;

use App\Modules\Core\Actions\BaseCommandAction;
use App\Modules\Setting\Data\SettingData;
use App\Modules\Setting\Data\SettingEntryData;
use App\Modules\Setting\Enums\SettingGroup;
use Illuminate\Support\Collection;

final class BatchSetSettingAction extends BaseCommandAction
{
    public function __construct(protected readonly SetSettingAction $setSettingAction) {}

    public function execute(SettingEntryData ...$setting): Collection
    {
        return $this->transaction(function () use ($setting) {
            $results = collect();

            foreach ($setting as $entry) {
                $setting = $this->setSettingAction->execute(
                    new SettingData(
                        key: $entry->key,
                        value: $entry->value,
                        group: $entry->group ?? SettingGroup::default()->value,
                        description: $entry->description,
                        type: $entry->type,
                    ),
                );
                $results->push($setting);
            }

            $this->log('setting_batch_set', null, ['count' => $results->count()]);

            return $results;
        });
    }
}
