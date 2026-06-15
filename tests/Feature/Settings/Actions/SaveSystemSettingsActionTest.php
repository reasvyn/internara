<?php

declare(strict_types=1);
use App\Settings\Actions\BatchSetSettingAction;
use App\Settings\Actions\SaveSystemSettingsAction;
use App\Settings\Actions\SetSettingAction;
use App\Settings\Branding\Actions\UploadBrandAssetAction;
use App\Settings\Models\Setting;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('save system settings saves general branding and mail', function () {
    $action = new SaveSystemSettingsAction(
        new BatchSetSettingAction(new SetSettingAction),
        new UploadBrandAssetAction,
    );

    $action->execute(
        general: [
            'brand_name' => 'Test Brand',
            'site_title' => 'Test Site',
            'default_locale' => 'id',
            'active_academic_year' => '2026/2027',
        ],
        branding: [
            'primary_color' => '#059669',
            'secondary_color' => '#6b7280',
            'accent_color' => '#f97316',
            'base_color' => '#ffffff',
        ],
        mail: [
            'mail_from_address' => 'test@example.com',
            'mail_from_name' => 'Test',
            'mail_host' => 'smtp.example.com',
            'mail_port' => '587',
            'mail_encryption' => 'tls',
            'mail_username' => 'user',
            'mail_password' => '',
        ],
    );

    expect(Setting::where('key', 'brand_name')->exists())->toBeTrue();
    expect(Setting::where('key', 'primary_color')->exists())->toBeTrue();
    expect(Setting::where('key', 'mail_host')->exists())->toBeTrue();
});

test('save system settings encrypts mail password', function () {
    $action = new SaveSystemSettingsAction(
        new BatchSetSettingAction(new SetSettingAction),
        new UploadBrandAssetAction,
    );

    $action->execute(
        general: ['brand_name' => 'Test', 'site_title' => 'Test', 'default_locale' => 'id', 'active_academic_year' => '2026/2027'],
        branding: ['primary_color' => '#000000', 'secondary_color' => '#000000', 'accent_color' => '#000000', 'base_color' => '#ffffff'],
        mail: ['mail_from_address' => '', 'mail_from_name' => '', 'mail_host' => '', 'mail_port' => '587', 'mail_encryption' => 'tls', 'mail_username' => '', 'mail_password' => 's3cret'],
    );

    $passwordSetting = Setting::where('key', 'mail_password')->first();

    expect($passwordSetting)->not->toBeNull();
    expect($passwordSetting->type)->toBe('encrypted');
    expect($passwordSetting->getRawOriginal('value'))->toContain('eyJpdiI'); // encrypted payload prefix
});
