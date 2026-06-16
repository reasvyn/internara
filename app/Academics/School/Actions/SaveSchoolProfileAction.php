<?php

declare(strict_types=1);

namespace App\Academics\School\Actions;

use App\Core\Actions\BaseCommandAction;
use App\Settings\Actions\BatchSetSettingAction;
use App\Settings\Branding\Actions\UploadBrandAssetAction;
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
            $settings = [];

            foreach ($data as $key => $value) {
                $settings["school.{$key}"] = $value;
            }

            if ($logoFile instanceof UploadedFile) {
                $this->uploadBrandAsset->execute($logoFile);
            }

            $this->batchSetSetting->execute($settings);

            Cache::forget('school_entity');

            $this->log('school_profile_updated', null, ['keys' => array_keys($data)]);
        });
    }
}
