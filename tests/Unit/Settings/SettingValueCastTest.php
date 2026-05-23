<?php

declare(strict_types=1);

use App\Domain\Settings\Casts\SettingValueCast;
use App\Domain\Settings\Models\Setting;

describe('SettingValueCast', function () {
    it('returns null for null value', function () {
        $cast = new SettingValueCast;
        $setting = new Setting;

        $result = $cast->get($setting, 'value', null, ['type' => 'string']);

        expect($result)->toBeNull();
    });

    it('casts to boolean', function () {
        $cast = new SettingValueCast;
        $setting = new Setting;

        $result = $cast->get($setting, 'value', '1', ['type' => 'boolean']);

        expect($result)->toBeTrue();
    });

    it('casts to integer', function () {
        $cast = new SettingValueCast;
        $setting = new Setting;

        $result = $cast->get($setting, 'value', '42', ['type' => 'integer']);

        expect($result)->toBe(42);
    });

    it('casts to float', function () {
        $cast = new SettingValueCast;
        $setting = new Setting;

        $result = $cast->get($setting, 'value', '3.14', ['type' => 'float']);

        expect($result)->toBe(3.14);
    });

    it('returns string by default', function () {
        $cast = new SettingValueCast;
        $setting = new Setting;

        $result = $cast->get($setting, 'value', 'hello', ['type' => 'string']);

        expect($result)->toBe('hello');
    });

    it('sets string value', function () {
        $cast = new SettingValueCast;
        $setting = new Setting;

        $result = $cast->set($setting, 'value', 'hello', []);

        expect($result['value'])->toBe('hello')
            ->and($result['type'])->toBe('string');
    });

    it('sets integer value', function () {
        $cast = new SettingValueCast;
        $setting = new Setting;

        $result = $cast->set($setting, 'value', 42, []);

        expect($result['value'])->toBe('42')
            ->and($result['type'])->toBe('integer');
    });

    it('sets boolean value', function () {
        $cast = new SettingValueCast;
        $setting = new Setting;

        $result = $cast->set($setting, 'value', true, []);

        expect($result['value'])->toBe(1)
            ->and($result['type'])->toBe('boolean');
    });

    it('sets encrypted value', function () {
        $cast = new SettingValueCast;
        $setting = new Setting;

        $result = $cast->set($setting, 'value', 'secret', ['type' => 'encrypted']);

        expect($result['type'])->toBe('encrypted')
            ->and($result['value'])->not->toBe('secret');
    });

    it('sets and gets encrypted value round-trip', function () {
        $cast = new SettingValueCast;
        $setting = new Setting;
        $setting->id = 'test-id';

        $setResult = $cast->set($setting, 'value', 'my-secret', ['type' => 'encrypted']);

        $getResult = $cast->get($setting, 'value', $setResult['value'], ['type' => 'encrypted']);

        expect($getResult)->toBe('my-secret');
    });

    it('returns null for null type', function () {
        $cast = new SettingValueCast;
        $setting = new Setting;

        $result = $cast->get($setting, 'value', 'ignored', ['type' => 'null']);

        expect($result)->toBeNull();
    });

    it('handles json value', function () {
        $cast = new SettingValueCast;
        $setting = new Setting;

        $result = $cast->get($setting, 'value', '{"key":"val"}', ['type' => 'json']);

        expect($result)->toBe(['key' => 'val']);
    });

    it('returns empty array for invalid json', function () {
        $cast = new SettingValueCast;
        $setting = new Setting;
        $setting->id = 'test-id';

        $result = $cast->get($setting, 'value', '{invalid}', ['type' => 'json']);

        expect($result)->toBe([]);
    });
});
