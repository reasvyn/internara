<?php

declare(strict_types=1);

namespace App\Domain\SysAdmin\Aggregates\Setting\Actions;

use App\Domain\Core\Actions\BaseAction;
use App\Domain\Core\Support\Locale;
use App\Domain\Core\Support\SmartLogger;
use Illuminate\Http\UploadedFile;

class SaveSystemSettingsAction extends BaseAction
{
    public function __construct(
        protected readonly BatchSetSettingAction $batchSetSetting,
        protected readonly UploadBrandAssetAction $uploadBrand,
    ) {}

    public function execute(
        array $general,
        array $branding,
        array $mail,
    ): void {
        $settings = [
            'brand_name' => $general['brand_name'] ?? '',
            'site_title' => $general['site_title'] ?? '',
            'default_locale' => $general['default_locale'] ?? Locale::DEFAULT_LOCALE,
            'active_academic_year' => $general['active_academic_year'] ?? '',
            'primary_color' => $branding['primary_color'] ?? '',
            'secondary_color' => $branding['secondary_color'] ?? '',
            'accent_color' => $branding['accent_color'] ?? '',
            'base_color' => $branding['base_color'] ?? '',
            'mail_from_address' => $mail['mail_from_address'] ?? '',
            'mail_from_name' => $mail['mail_from_name'] ?? '',
            'mail_host' => $mail['mail_host'] ?? '',
            'mail_port' => $mail['mail_port'] ?? '587',
            'mail_encryption' => $mail['mail_encryption'] ?? 'tls',
            'mail_username' => $mail['mail_username'] ?? '',
        ];

        if (! empty($mail['mail_password'])) {
            $settings['mail_password'] = [
                'value' => $mail['mail_password'],
                'type' => 'encrypted',
            ];
        }

        $brandLogo = $branding['brand_logo'] ?? null;
        if ($brandLogo instanceof UploadedFile) {
            $settings['brand_logo'] = $this->uploadBrand->execute($brandLogo);
        }

        $siteFavicon = $branding['site_favicon'] ?? null;
        if ($siteFavicon instanceof UploadedFile) {
            $settings['site_favicon'] = $this->uploadBrand->execute($siteFavicon, 'favicon');
        }

        $this->batchSetSetting->execute($settings);

        SmartLogger::info('settings_updated')
            ->event('settings_updated')
            ->module('Setting')
            ->withPayload(['keys' => array_keys($settings)])
            ->activityOnly()
            ->save();
    }
}
