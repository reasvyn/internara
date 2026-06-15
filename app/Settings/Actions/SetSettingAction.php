<?php

declare(strict_types=1);

namespace App\Settings\Actions;

use App\Core\Actions\BaseCommandAction;
use App\Settings\Data\SettingData;
use App\Settings\Events\SettingUpdated;
use App\Settings\Models\Setting;
use App\Settings\Rules\ValidSettingKey;
use Illuminate\Support\Facades\Validator;

class SetSettingAction extends BaseCommandAction
{
    public function execute(
        string $key,
        mixed $value,
        ?string $group = null,
        ?string $description = null,
        ?string $type = null,
    ): Setting {
        Validator::validate(
            ['key' => $key],
            ['key' => ['required', new ValidSettingKey]],
        );

        return $this->transaction(function () use ($key, $value, $group, $description, $type) {
            $setting = Setting::updateOrCreate(['key' => $key]);
            $setting->type = $type ?? $this->detectType($value);
            $setting->value = $value;
            $setting->group = $group;
            $setting->description = $description;
            $setting->save();

            $this->log('setting.updated', $setting, [
                'key' => $key,
                'group' => $group,
                'type' => $setting->type,
            ]);

            $this->dispatchEvent(new SettingUpdated(
                setting: new SettingData(
                    key: $key,
                    value: $value,
                    type: $setting->type,
                    group: $group,
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
