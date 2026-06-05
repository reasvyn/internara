<?php

declare(strict_types=1);

namespace App\SysAdmin\Setting\Actions;

use App\Core\Actions\BaseAction;
use App\SysAdmin\Setting\Models\Setting;
use App\SysAdmin\Setting\Rules\ValidSettingKey;
use App\SysAdmin\Setting\Support\Settings;
use Illuminate\Support\Facades\Validator;

class SetSettingAction extends BaseAction
{
    public function execute(
        string $key,
        mixed $value,
        ?string $group = null,
        ?string $description = null,
        ?string $type = null,
    ): Setting {
        Validator::validate(['key' => $key], [
            'key' => ['required', new ValidSettingKey],
        ]);

        $detectedType = $type ?? $this->detectType($value);

        $setting = Setting::updateOrCreate(
            ['key' => $key],
            [
                'value' => $value,
                'type' => $detectedType,
                'group' => $group,
                'description' => $description,
            ],
        );

        Settings::forget($key, $setting->group);

        return $setting;
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
