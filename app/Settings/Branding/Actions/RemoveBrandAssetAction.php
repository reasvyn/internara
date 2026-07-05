<?php

declare(strict_types=1);

namespace App\Settings\Branding\Actions;

use App\Core\Actions\BaseCommandAction;
use App\Settings\Actions\SetSettingAction;
use App\Settings\Enums\MediaCollection;
use App\Settings\Models\Setting;

class RemoveBrandAssetAction extends BaseCommandAction
{
    public function __construct(protected readonly SetSettingAction $setSetting) {}

    public function execute(string $type): void
    {
        $this->transaction(function () use ($type) {
            $collection = $type === 'favicon' ? MediaCollection::FAVICON : MediaCollection::LOGO;
            $settingKey = $type === 'favicon' ? 'brand_favicon_ref' : 'brand_logo_ref';
            $settingsKey = $type === 'favicon' ? 'site_favicon' : 'brand_logo';

            $setting = Setting::firstOrCreate(['key' => $settingKey]);

            $medias = $setting->getMedia($collection->value);
            foreach ($medias as $media) {
                $properties = $media->getCustomProperties();
                if ($type === 'logo' && ($properties['type'] ?? '') !== 'logo') {
                    continue;
                }
                $media->delete();
            }

            $this->setSetting->execute(key: $settingsKey, value: '');

            $this->log('brand_asset_removed', $setting, ['type' => $type]);
        });
    }
}
