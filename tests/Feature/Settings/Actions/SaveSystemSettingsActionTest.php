<?php

declare(strict_types=1);

use App\Domain\Settings\Actions\SaveSystemSettingsAction;
use App\Domain\Settings\Models\Setting;
use App\Domain\Settings\Support\Settings;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    Settings::clearOverrides();
    Cache::flush();
});

describe('SaveSystemSettingsAction', function () {
    it('saves general settings', function () {
        app(SaveSystemSettingsAction::class)->execute(
            general: [
                'brand_name' => 'Test Corp',
                'site_title' => 'Test Site',
                'default_locale' => 'id',
                'active_academic_year' => '2025/2026',
            ],
            branding: [],
            mail: [],
        );

        expect(Settings::get('brand_name'))->toBe('Test Corp');
        expect(Settings::get('site_title'))->toBe('Test Site');
        expect(Settings::get('default_locale'))->toBe('id');
    });

    it('saves branding color settings', function () {
        app(SaveSystemSettingsAction::class)->execute(
            general: ['brand_name' => 'X', 'site_title' => 'X', 'default_locale' => 'id', 'active_academic_year' => '2025/2026'],
            branding: [
                'primary_color' => '#ff0000',
                'secondary_color' => '#00ff00',
                'accent_color' => '#0000ff',
                'base_color' => '#ffffff',
            ],
            mail: [],
        );

        expect(Settings::get('primary_color'))->toBe('#ff0000');
        expect(Settings::get('secondary_color'))->toBe('#00ff00');
        expect(Settings::get('accent_color'))->toBe('#0000ff');
        expect(Settings::get('base_color'))->toBe('#ffffff');
    });

    it('saves mail settings', function () {
        app(SaveSystemSettingsAction::class)->execute(
            general: ['brand_name' => 'X', 'site_title' => 'X', 'default_locale' => 'id', 'active_academic_year' => '2025/2026'],
            branding: [],
            mail: [
                'mail_from_address' => 'noreply@test.com',
                'mail_from_name' => 'Test',
                'mail_host' => 'smtp.test.com',
                'mail_port' => '587',
                'mail_encryption' => 'tls',
                'mail_username' => 'user',
            ],
        );

        expect(Settings::get('mail_from_address'))->toBe('noreply@test.com');
        expect(Settings::get('mail_host'))->toBe('smtp.test.com');
        expect(Settings::get('mail_port'))->toBe('587');
    });

    it('saves encrypted mail password when provided', function () {
        app(SaveSystemSettingsAction::class)->execute(
            general: ['brand_name' => 'X', 'site_title' => 'X', 'default_locale' => 'id', 'active_academic_year' => '2025/2026'],
            branding: [],
            mail: ['mail_password' => 'secret123'],
        );

        $setting = Setting::where('key', 'mail_password')->first();

        expect($setting)->not->toBeNull();
        expect($setting->value)->toBe('secret123');
        expect($setting->type)->toBe('encrypted');
    });

    it('uses defaults for empty mail values', function () {
        app(SaveSystemSettingsAction::class)->execute(
            general: ['brand_name' => 'X', 'site_title' => 'X', 'default_locale' => 'id', 'active_academic_year' => '2025/2026'],
            branding: [],
            mail: [],
        );

        expect(Settings::get('mail_port'))->toBe('587');
        expect(Settings::get('mail_encryption'))->toBe('tls');
    });
});
