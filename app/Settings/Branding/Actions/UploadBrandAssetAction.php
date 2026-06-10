<?php

declare(strict_types=1);

namespace App\Settings\Branding\Actions;

use App\Core\Actions\BaseAction;
use App\Settings\Enums\MediaCollection;
use App\Settings\Models\Setting;
use Illuminate\Http\UploadedFile;

class UploadBrandAssetAction extends BaseAction
{
    public function execute(UploadedFile $file, string $type = 'logo'): string
    {
        $collection = $type === 'favicon' ? MediaCollection::FAVICON : MediaCollection::LOGO;

        $setting = Setting::firstOrCreate(['key' => $collection->value.'_ref']);

        $setting
            ->addMedia($file)
            ->withCustomProperties(['type' => $type])
            ->toMediaCollection($collection->value);

        return $setting->getFirstMediaUrl($collection->value, 'thumb');
    }
}
