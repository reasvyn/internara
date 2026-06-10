<?php

declare(strict_types=1);

namespace App\Settings\Actions;

use App\Core\Actions\BaseAction;
use App\Settings\Data\SettingData;
use App\Settings\Events\SettingUpdated;
use App\Settings\Models\Setting;

class DeleteSettingAction extends BaseAction
{
    public function execute(string|array $keys): int
    {
        $keys = is_array($keys) ? $keys : [$keys];

        return $this->transaction(function () use ($keys) {
            $deleted = Setting::whereIn('key', $keys)->delete();

            foreach ($keys as $key) {
                $this->dispatchEvent(new SettingUpdated(
                    setting: new SettingData(key: $key),
                    wasRecentlyCreated: false,
                ));
            }

            return $deleted;
        });
    }
}
