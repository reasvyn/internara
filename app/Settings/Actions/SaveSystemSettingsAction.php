<?php

declare(strict_types=1);

namespace App\Settings\Actions;

use App\Core\Actions\BaseCommandAction;
use App\Settings\Branding\Actions\UploadBrandAssetAction;
use App\Settings\Data\SettingEntryData;
use App\Settings\Data\SystemSettingsData;
use App\Settings\Locale\Support\Locale;
use Illuminate\Http\UploadedFile;

class SaveSystemSettingsAction extends BaseCommandAction
{
    public function __construct(
        protected readonly BatchSetSettingAction $batchSetSetting,
        protected readonly UploadBrandAssetAction $uploadBrand,
    ) {}

    public function execute(SystemSettingsData $data): void
    {
        $this->transaction(function () use ($data) {
            $entries = [];

            $add = function (string $key, mixed $value, ?string $type = null) use (&$entries): void {
                if ($value !== null && $value !== '') {
                    $entries[] = new SettingEntryData(key: $key, value: $value, type: $type);
                }
            };

            $add('brand_name', $data->brandName);
            $add('site_title', $data->siteTitle);
            $add('support_email', $data->supportEmail);
            $add('default_locale', $data->defaultLocale ?: Locale::DEFAULT_LOCALE);
            $add('active_academic_year', $data->activeAcademicYear);
            $add('primary_color', $data->primaryColor);
            $add('secondary_color', $data->secondaryColor);
            $add('accent_color', $data->accentColor);
            $add('base_color', $data->baseColor);
            $add('mail_from_address', $data->mailFromAddress);
            $add('mail_from_name', $data->mailFromName);
            $add('mail_host', $data->mailHost);
            $add('mail_port', $data->mailPort ?: '587');
            $add('mail_encryption', $data->mailEncryption ?: 'tls');
            $add('mail_username', $data->mailUsername);

            if ($data->mailPassword !== null && $data->mailPassword !== '') {
                $add('mail_password', $data->mailPassword, 'encrypted');
            }

            if ($data->brandLogo instanceof UploadedFile) {
                $add('brand_logo', $this->uploadBrand->execute($data->brandLogo));
            }

            if ($data->siteFavicon instanceof UploadedFile) {
                $add('site_favicon', $this->uploadBrand->execute($data->siteFavicon, 'favicon'));
            }

            if ($entries !== []) {
                $this->batchSetSetting->execute(...$entries);
            }

            $this->log('settings_updated', null, ['keys' => array_column(
                array_map(fn (SettingEntryData $e) => ['key' => $e->key], $entries),
                'key',
            )]);
        });
    }
}
