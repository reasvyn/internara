<?php

declare(strict_types=1);

namespace App\Settings\Actions;

use App\Core\Actions\BaseAction;
use App\Settings\Models\Setting;
use App\Settings\Support\Settings;
use Illuminate\Support\Facades\DB;

class DeleteSettingAction extends BaseAction
{
    public function execute(string|array $keys): int
    {
        $keys = is_array($keys) ? $keys : [$keys];

        return DB::transaction(function () use ($keys) {
            $deleted = Setting::whereIn('key', $keys)->delete();

            foreach ($keys as $key) {
                Settings::forget($key);
            }

            return $deleted;
        });
    }
}
