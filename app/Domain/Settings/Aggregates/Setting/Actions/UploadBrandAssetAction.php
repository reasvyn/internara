<?php

declare(strict_types=1);

namespace App\Domain\Settings\Aggregates\Setting\Actions;

use App\Domain\Core\Actions\BaseAction;
use App\Domain\Settings\Aggregates\Setting\Models\Setting;
use Illuminate\Http\UploadedFile;

class UploadBrandAssetAction extends BaseAction
{
    public function execute(UploadedFile $file, string $type = 'logo'): string
    {
        $collection = $type === 'favicon'
            ? Setting::COLLECTION_FAVICON
            : Setting::COLLECTION_LOGO;

        $setting = Setting::firstOrCreate(['key' => $collection.'_ref']);

        $setting->addMedia($file)
            ->withCustomProperties(['type' => $type])
            ->toMediaCollection($collection);

        return $setting->getFirstMediaUrl($collection, 'thumb');
    }
}
