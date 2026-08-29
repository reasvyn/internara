<?php

declare(strict_types=1);

namespace App\Modules\Settings\Actions;

use App\Modules\Core\Actions\BaseCommandAction;
use App\Modules\Settings\Data\SettingData;
use App\Modules\Settings\Events\SettingUpdated;
use App\Modules\Settings\Models\Setting;
use App\Modules\Settings\Rules\ValidSettingKey;
use Illuminate\Support\Facades\Validator;

final class SetSettingAction extends BaseCommandAction
{
    public function execute(SettingData $data): Setting
    {
        Validator::validate(
            ['key' => $data->key],
            ['key' => ['required', new ValidSettingKey]],
        );

        return $this->transaction(function () use ($data) {
            $setting = Setting::updateOrCreate(['key' => $data->key]);
            $setting->type = $data->type ?? $this->detectType($data->value);
            $setting->value = $data->value;
            $setting->group = $data->group;
            $setting->description = $data->description;
            $setting->save();

            $this->log('setting.updated', $setting, [
                'key' => $data->key,
                'group' => $data->group,
                'type' => $setting->type,
            ]);

            $this->dispatchEvent(new SettingUpdated(
                setting: new SettingData(
                    key: $data->key,
                    value: $data->value,
                    type: $setting->type,
                    group: $data->group,
                ),
                wasRecentlyCreated: $setting->wasRecentlyCreated,
            ));

            return $setting;
        });
    }

    protected function detectType(mixed $value): string
    {
        return match (true) {
            is_bool($value) => 'boolean',
            is_int($value) => 'integer',
            is_float($value) => 'float',
            is_array($value) => 'json',
            default => 'string',
        };
    }
}
