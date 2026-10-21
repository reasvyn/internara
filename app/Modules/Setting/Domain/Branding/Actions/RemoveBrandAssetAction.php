<?php

declare(strict_types=1);

namespace App\Modules\Setting\Domain\Branding\Actions;

use App\Modules\Core\Actions\BaseCommandAction;
use App\Modules\Setting\Actions\SetSettingAction;
use App\Modules\Setting\Data\SettingData;
use App\Modules\Setting\Enums\MediaCollection;
use App\Modules\Setting\Models\Setting;

final class RemoveBrandAssetAction extends BaseCommandAction
{
    public function __construct(protected readonly SetSettingAction $setSetting) {}

    public function execute(string $type): void
    {
        $this->transaction(function () use ($type) {
            $collection = $type === 'favicon' ? MediaCollection::FAVICON : MediaCollection::LOGO;
            $refKey = $type === 'favicon' ? 'brand_favicon_ref' : 'brand_logo_ref';
            $settingKey = $type === 'favicon' ? 'site_favicon' : 'brand_logo';

            $setting = Setting::where('key', $refKey)->first();

            if ($setting !== null) {
                $medias = $setting->getMedia($collection->value);
                foreach ($medias as $media) {
                    $mediaType = $media->getCustomProperty('type');
                    if ($type === 'logo' && $mediaType !== null && $mediaType !== 'logo') {
                        continue;
                    }
                    $media->delete();
                }
            }

            $this->setSetting->execute(new SettingData(
                key: $settingKey,
                value: '',
                group: 'branding',
            ));

            $this->log('brand_asset_removed', $setting ?? Setting::firstOrCreate(['key' => $settingKey]), ['type' => $type]);
        });
    }
}
