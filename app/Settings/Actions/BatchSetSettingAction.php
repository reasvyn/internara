<?php

declare(strict_types=1);

namespace App\Settings\Actions;

use App\Core\Actions\BaseCommandAction;
use App\Settings\Data\SettingEntryData;
use App\Settings\Enums\SettingGroup;
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
                    $entry->key,
                    $entry->value,
                    $entry->group ?? SettingGroup::default()->value,
                    $entry->description,
                    $entry->type,
                );
                $results->push($setting);
            }

            $this->log('settings_batch_set', null, ['count' => $results->count()]);

            return $results;
        });
    }
}
