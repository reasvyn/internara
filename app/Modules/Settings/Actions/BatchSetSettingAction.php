<?php

declare(strict_types=1);

namespace App\Modules\Settings\Actions;

use App\Modules\Core\Actions\BaseCommandAction;
use App\Modules\Settings\Data\SettingData;
use App\Modules\Settings\Data\SettingEntryData;
use App\Modules\Settings\Enums\SettingGroup;
use Illuminate\Support\Collection;

final class BatchSetSettingAction extends BaseCommandAction
{
    public function __construct(protected readonly SetSettingAction $setSettingAction) {}

    public function execute(SettingEntryData ...$settings): Collection
    {
        return $this->transaction(function () use ($settings) {
            $results = collect();

            foreach ($settings as $entry) {
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

            $this->log('settings_batch_set', null, ['count' => $results->count()]);

            return $results;
        });
    }
}
