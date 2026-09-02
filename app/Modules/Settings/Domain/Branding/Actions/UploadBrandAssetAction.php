<?php

declare(strict_types=1);

namespace App\Modules\Settings\Domain\Branding\Actions;

use App\Modules\Core\Actions\BaseCommandAction;
use App\Modules\Core\Exceptions\RejectedException;
use App\Modules\Settings\Enums\MediaCollection;
use App\Modules\Settings\Models\Setting;
use Illuminate\Http\UploadedFile;

final class UploadBrandAssetAction extends BaseCommandAction
{
    public function execute(UploadedFile $file, string $type = 'logo'): string
    {
        return $this->transaction(function () use ($file, $type) {
            $collection = $type === 'favicon' ? MediaCollection::FAVICON : MediaCollection::LOGO;

            $setting = Setting::firstOrCreate(['key' => $collection->value.'_ref']);

            if (! $file->isValid()) {
                throw new RejectedException(__('setting.messages.invalid_file_upload'));
            }

            $setting
                ->addMedia($file)
                ->withCustomProperties(['type' => $type])
                ->toMediaCollection($collection->value);

            $url = $setting->getFirstMediaUrl($collection->value, 'thumb');

            $this->log('brand_asset_uploaded', $setting, ['type' => $type, 'url' => $url]);

            return $url;
        });
    }
}
