<?php

declare(strict_types=1);

use App\Domain\Settings\Actions\SetSettingAction;
use App\Domain\Settings\Models\Setting;

describe('SetSettingAction', function () {
    beforeEach(function () {
        Setting::truncate();
    });

    it('creates a new setting', function () {
        $action = app(SetSettingAction::class);

        $setting = $action->execute('test_key', 'test_value', 'test_group');

        expect($setting->key)->toBe('test_key')
            ->and($setting->value)->toBe('test_value')
            ->and($setting->group)->toBe('test_group');
    });

    it('updates existing setting', function () {
        Setting::create(['key' => 'test_key', 'value' => 'old', 'group' => 'g']);
        $action = app(SetSettingAction::class);

        $setting = $action->execute('test_key', 'new_value', 'updated_group');

        expect($setting->value)->toBe('new_value')
            ->and($setting->group)->toBe('updated_group');
    });

    it('detects boolean type', function () {
        $action = app(SetSettingAction::class);

        $setting = $action->execute('flag', true);

        expect($setting->type)->toBe('boolean');
    });

    it('detects integer type', function () {
        $action = app(SetSettingAction::class);

        $setting = $action->execute('count', 42);

        expect($setting->type)->toBe('integer');
    });

    it('detects json type for arrays', function () {
        $action = app(SetSettingAction::class);

        $setting = $action->execute('items', ['a', 'b']);

        expect($setting->type)->toBe('json');
    });
});
