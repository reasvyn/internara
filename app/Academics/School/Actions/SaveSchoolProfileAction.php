<?php

declare(strict_types=1);

namespace App\Academics\School\Actions;

use App\Core\Actions\BaseCommandAction;
use App\Settings\Actions\BatchSetSettingAction;
use App\Settings\Branding\Actions\UploadBrandAssetAction;
use App\Settings\Data\SettingEntryData;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;

class SaveSchoolProfileAction extends BaseCommandAction
{
    public function __construct(
        protected readonly BatchSetSettingAction $batchSetSetting,
        protected readonly UploadBrandAssetAction $uploadBrandAsset,
    ) {}

    public function execute(array $data, ?UploadedFile $logoFile = null): void
    {
        $this->transaction(function () use ($data, $logoFile) {
            $entries = [];

            foreach ($data as $key => $value) {
                $entries[] = new SettingEntryData(key: "school.{$key}", value: $value);
            }

            if ($logoFile instanceof UploadedFile) {
                $this->uploadBrandAsset->execute($logoFile);
            }

            if ($entries !== []) {
                $this->batchSetSetting->execute(...$entries);
            }

            Cache::forget(config('cache-keys.school_entity'));

            $this->log('school_profile_updated', null, ['keys' => array_keys($data)]);
        });
    }
}
