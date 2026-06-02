<?php

declare(strict_types=1);

use App\Domain\Core\Support\CacheKeys;
use App\Domain\Settings\Actions\SetSettingAction;
use App\Domain\Settings\Models\Setting;
use App\Domain\Settings\Support\Settings;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;

uses(LazilyRefreshDatabase::class);

describe('SetSettingAction', function () {
    beforeEach(function () {
        Settings::clearOverrides();
        Cache::flush();
    });

    it('creates a new string setting', function () {
        $setting = app(SetSettingAction::class)->execute('site_name', 'Internara');

        expect($setting)->toBeInstanceOf(Setting::class)
            ->and($setting->key)->toBe('site_name')
            ->and($setting->value)->toBe('Internara')
            ->and($setting->type)->toBe('string');
    });

    it('creates a new boolean setting', function () {
        $setting = app(SetSettingAction::class)->execute('feature_x', true);

        expect($setting->value)->toBeTrue()
            ->and($setting->type)->toBe('boolean');
    });

    it('creates a new integer setting', function () {
        $setting = app(SetSettingAction::class)->execute('max_items', 100);

        expect($setting->value)->toBe(100)
            ->and($setting->type)->toBe('integer');
    });

    it('creates a new json setting', function () {
        $setting = app(SetSettingAction::class)->execute('options', ['key' => 'val']);

        expect($setting->value)->toBe(['key' => 'val'])
            ->and($setting->type)->toBe('json');
    });

    it('updates an existing setting', function () {
        Setting::create(['key' => 'site_name', 'value' => 'Old', 'type' => 'string']);

        $setting = app(SetSettingAction::class)->execute('site_name', 'New');

        expect($setting->value)->toBe('New');
    });

    it('validates key format', function () {
        expect(fn () => app(SetSettingAction::class)->execute('InvalidKey!', 'x'))
            ->toThrow(ValidationException::class);
    });

    it('accepts optional group and description', function () {
        $setting = app(SetSettingAction::class)->execute(
            key: 'mail_host',
            value: 'smtp.example.com',
            group: 'mail',
            description: 'SMTP hostname',
        );

        expect($setting->group)->toBe('mail')
            ->and($setting->description)->toBe('SMTP hostname');
    });

    it('clears settings cache after update', function () {
        Cache::put(CacheKeys::SETTINGS_KEY.'test_key', 'old', 3600);

        app(SetSettingAction::class)->execute('test_key', 'new_value');

        expect(Cache::has(CacheKeys::SETTINGS_KEY.'test_key'))->toBeFalse();
    });
});
