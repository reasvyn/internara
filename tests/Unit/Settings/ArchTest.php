<?php

declare(strict_types=1);

use App\Domain\Settings\Actions\BatchSetSettingAction;
use App\Domain\Settings\Actions\SetSettingAction;
use App\Domain\Settings\Actions\TestMailSettingsAction;
use App\Domain\Settings\Actions\UploadBrandAssetAction;
use App\Domain\Settings\Models\Setting;
use App\Domain\Settings\Rules\ValidSettingKey;
use App\Domain\Settings\Support\AppInfo;
use App\Domain\Settings\Support\AppMetadata;
use App\Domain\Settings\Support\Settings;

arch('Settings actions extend BaseAction')
    ->expect(SetSettingAction::class)
    ->toExtend('App\Domain\Core\Actions\BaseAction')
    ->and(BatchSetSettingAction::class)
    ->toExtend('App\Domain\Core\Actions\BaseAction')
    ->and(TestMailSettingsAction::class)
    ->toExtend('App\Domain\Core\Actions\BaseAction')
    ->and(UploadBrandAssetAction::class)
    ->toExtend('App\Domain\Core\Actions\BaseAction');

arch('Settings model extends BaseModel')
    ->expect(Setting::class)
    ->toExtend('App\Domain\Core\Models\BaseModel');

arch('Settings support classes are final')
    ->expect(Settings::class)
    ->toBeFinal()
    ->and(AppInfo::class)
    ->toBeFinal()
    ->and(AppMetadata::class)
    ->toBeFinal();

arch('ValidSettingKey is final')
    ->expect(ValidSettingKey::class)
    ->toBeFinal();
