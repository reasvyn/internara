<?php

declare(strict_types=1);
use App\Settings\Actions\BatchSetSettingAction;
use App\Settings\Actions\SaveSystemSettingsAction;
use App\Settings\Actions\SetSettingAction;
use App\Settings\Branding\Actions\UploadBrandAssetAction;
use App\Settings\Data\SystemSettingsData;
use App\Settings\Models\Setting;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('save system settings saves general branding and mail', function () {
    $action = new SaveSystemSettingsAction(
        new BatchSetSettingAction(new SetSettingAction),
        new UploadBrandAssetAction,
    );

    $data = new SystemSettingsData(
        brandName: 'Test Brand',
        siteTitle: 'Test Site',
        defaultLocale: 'id',
        activeAcademicYear: '2026/2027',
        primaryColor: '#059669',
        secondaryColor: '#6b7280',
        accentColor: '#f97316',
        baseColor: '#ffffff',
        mailFromAddress: 'test@example.com',
        mailFromName: 'Test',
        mailHost: 'smtp.example.com',
        mailPort: '587',
        mailEncryption: 'tls',
        mailUsername: 'user',
        mailPassword: null,
    );

    $action->execute($data);

    expect(Setting::where('key', 'brand_name')->exists())->toBeTrue();
    expect(Setting::where('key', 'primary_color')->exists())->toBeTrue();
    expect(Setting::where('key', 'mail_host')->exists())->toBeTrue();
});

test('save system settings encrypts mail password', function () {
    $action = new SaveSystemSettingsAction(
        new BatchSetSettingAction(new SetSettingAction),
        new UploadBrandAssetAction,
    );

    $data = new SystemSettingsData(
        brandName: 'Test',
        siteTitle: 'Test',
        defaultLocale: 'id',
        activeAcademicYear: '2026/2027',
        primaryColor: '#000000',
        secondaryColor: '#000000',
        accentColor: '#000000',
        baseColor: '#ffffff',
        mailPassword: 's3cret',
    );

    $action->execute($data);

    $passwordSetting = Setting::where('key', 'mail_password')->first();

    expect($passwordSetting)->not->toBeNull();
    expect($passwordSetting->type)->toBe('encrypted');
    expect($passwordSetting->getRawOriginal('value'))->toContain('eyJpdiI');
});
