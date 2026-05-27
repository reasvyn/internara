<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

use App\Domain\School\Models\AcademicYear;
use App\Domain\Settings\Actions\GetAcademicYearsAction;
use App\Domain\Settings\Actions\SaveSystemSettingsAction;
use App\Domain\Settings\Models\Setting;
use App\Domain\Settings\Support\Settings;
use Illuminate\Http\UploadedFile;

beforeEach(function () {
    Settings::clearOverrides();
});

describe('SaveSystemSettingsAction', function () {
    it('saves all general settings', function () {
        app(SaveSystemSettingsAction::class)->execute(
            general: [
                'brand_name' => 'Test School',
                'site_title' => 'PKL System',
                'default_locale' => 'en',
                'active_academic_year' => '2025/2026',
            ],
            branding: [
                'primary_color' => '#3b82f6',
                'secondary_color' => '#8b5cf6',
                'accent_color' => '#f59e0b',
                'base_color' => '#ffffff',
                'brand_logo' => null,
                'site_favicon' => null,
            ],
            mail: [
                'mail_from_address' => '',
                'mail_from_name' => '',
                'mail_host' => '',
                'mail_port' => '587',
                'mail_encryption' => 'tls',
                'mail_username' => '',
            ],
        );

        expect(Settings::get('brand_name'))->toBe('Test School')
            ->and(Settings::get('site_title'))->toBe('PKL System')
            ->and(Settings::get('site_title'))->toBe('PKL System');
    });

    it('saves mail password as encrypted type', function () {
        app(SaveSystemSettingsAction::class)->execute(
            general: ['brand_name' => 'S', 'site_title' => 'S', 'default_locale' => 'en', 'active_academic_year' => '2025/2026'],
            branding: ['primary_color' => '#000', 'secondary_color' => '#000', 'accent_color' => '#000', 'base_color' => '#fff', 'brand_logo' => null, 'site_favicon' => null],
            mail: ['mail_from_address' => '', 'mail_from_name' => '', 'mail_host' => '', 'mail_port' => '587', 'mail_encryption' => 'tls', 'mail_username' => '', 'mail_password' => 'secret'],
        );

        $setting = Setting::where('key', 'mail_password')->first();
        expect($setting->type)->toBe('encrypted');
    });

    it('uploads brand logo file', function () {
        $file = UploadedFile::fake()->image('brand-logo.png');

        app(SaveSystemSettingsAction::class)->execute(
            general: ['brand_name' => 'S', 'site_title' => 'S', 'default_locale' => 'en', 'active_academic_year' => '2025/2026'],
            branding: ['primary_color' => '#000', 'secondary_color' => '#000', 'accent_color' => '#000', 'base_color' => '#fff', 'brand_logo' => $file, 'site_favicon' => null],
            mail: ['mail_from_address' => '', 'mail_from_name' => '', 'mail_host' => '', 'mail_port' => '587', 'mail_encryption' => 'tls', 'mail_username' => ''],
        );

        expect(Settings::has('brand_logo'))->toBeTrue();
    });
});

describe('GetAcademicYearsAction', function () {
    it('returns academic years ordered by start_date desc', function () {
        AcademicYear::factory()->create(['name' => '2024/2025', 'start_date' => '2024-07-01', 'end_date' => '2025-06-30']);
        AcademicYear::factory()->create(['name' => '2025/2026', 'start_date' => '2025-07-01', 'end_date' => '2026-06-30']);

        $years = app(GetAcademicYearsAction::class)->execute();

        expect($years)->toHaveCount(2)
            ->and($years->first()['name'])->toBe('2025/2026');
    });

    it('returns empty collection when no years exist', function () {
        expect(app(GetAcademicYearsAction::class)->execute())->toBeEmpty();
    });
});

describe('Settings database integration', function () {
    it('sets a new setting', function () {
        Settings::set(['test_key' => 'test_value']);

        expect(Setting::where('key', 'test_key')->exists())->toBeTrue()
            ->and(Settings::get('test_key'))->toBe('test_value');
    });

    it('updates existing setting', function () {
        Setting::create(['key' => 'update_me', 'value' => 'old', 'group' => 'test', 'type' => 'string']);

        Settings::set(['update_me' => 'new_value']);

        expect(Setting::where('key', 'update_me')->first()->value)->toBe('new_value');
    });

    it('sets setting with group and type', function () {
        Settings::set(['custom_setting' => ['value' => 'hello', 'group' => 'custom', 'type' => 'string']]);

        $setting = Setting::where('key', 'custom_setting')->first();
        expect($setting->group)->toBe('custom');
    });

    it('returns all settings as collection', function () {
        Setting::create(['key' => 'key_a', 'value' => 'a', 'group' => 'test', 'type' => 'string']);
        Setting::create(['key' => 'key_b', 'value' => 'b', 'group' => 'test', 'type' => 'string']);

        $all = Settings::all(true);

        expect($all)->toHaveKeys(['key_a', 'key_b']);
    });

    it('checks group existence', function () {
        expect(Settings::hasGroup('nonexistent'))->toBeFalse();

        Setting::create(['key' => 'test', 'value' => 'x', 'group' => 'mygroup', 'type' => 'string']);

        expect(Settings::hasGroup('mygroup'))->toBeTrue();
    });

    it('forgets a single key cache', function () {
        Setting::create(['key' => 'cache_me', 'value' => 'cached', 'group' => 'test', 'type' => 'string']);

        expect(Settings::get('cache_me'))->toBe('cached');

        Settings::forget('cache_me', 'test');

        Setting::where('key', 'cache_me')->update(['value' => 'updated']);

        expect(Settings::get('cache_me', null, true))->toBe('updated');
    });

    it('returns all keys', function () {
        Setting::create(['key' => 'key_x', 'value' => '1', 'group' => 'g', 'type' => 'string']);
        Setting::create(['key' => 'key_y', 'value' => '2', 'group' => 'g', 'type' => 'string']);

        $keys = Settings::keys(true);

        expect($keys)->toContain('key_x', 'key_y');
    });

    it('counts settings by group', function () {
        Setting::create(['key' => 'a1', 'value' => '1', 'group' => 'alpha', 'type' => 'string']);
        Setting::create(['key' => 'a2', 'value' => '2', 'group' => 'alpha', 'type' => 'string']);
        Setting::create(['key' => 'b1', 'value' => '1', 'group' => 'beta', 'type' => 'string']);

        $counts = Settings::countByGroup();

        expect($counts['alpha'] ?? 0)->toBe(2)
            ->and($counts['beta'] ?? 0)->toBe(1);
    });

    it('lists distinct groups', function () {
        Setting::create(['key' => 'x', 'value' => '1', 'group' => 'group_a', 'type' => 'string']);
        Setting::create(['key' => 'y', 'value' => '2', 'group' => 'group_b', 'type' => 'string']);

        $groups = Settings::groups();

        expect($groups)->toContain('group_a', 'group_b');
    });

    it('forgets entire group cache', function () {
        Setting::create(['key' => 'gk', 'value' => 'original', 'group' => 'testgroup', 'type' => 'string']);

        expect(Settings::get('gk'))->toBe('original');

        Setting::where('key', 'gk')->update(['value' => 'modified']);
        Settings::forgetGroup('testgroup');

        expect(Settings::get('gk', null, true))->toBe('modified');
    });

    it('has returns true for existing key', function () {
        Setting::create(['key' => 'existing', 'value' => 'yes', 'group' => 'g', 'type' => 'string']);

        expect(Settings::has('existing'))->toBeTrue();
    });
});
