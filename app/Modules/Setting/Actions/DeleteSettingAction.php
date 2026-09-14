<?php

declare(strict_types=1);

namespace App\Modules\Setting\Actions;

use App\Modules\Core\Actions\BaseCommandAction;
use App\Modules\Setting\Data\SettingData;
use App\Modules\Setting\Events\SettingUpdated;
use App\Modules\Setting\Models\Setting;

final class DeleteSettingAction extends BaseCommandAction
{
    /**
     * @param string|list<string> $keys
     */
    public function execute(string|array $keys): int
    {
        /** @var list<string> $keys */
        $keys = is_array($keys) ? $keys : [$keys];

        return $this->transaction(function () use ($keys) {
            $deleted = 0;

            foreach ($keys as $key) {
                $setting = Setting::query()->whereKey($key)->first();

                if ($setting === null) {
                    continue;
                }

                $setting->delete();
                $deleted++;

                $this->dispatchEvent(new SettingUpdated(
                    setting: new SettingData(key: $key),
                    wasRecentlyCreated: false,
                ));
            }

            $this->log('setting_deleted', null, ['keys' => $keys]);

            return $deleted;
        });
    }
}
