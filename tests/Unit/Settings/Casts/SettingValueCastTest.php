<?php

declare(strict_types=1);

use App\Domain\Settings\Models\Setting;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

describe('SettingValueCast', function () {
    describe('get', function () {
        it('returns null for null value', function () {
            $setting = Setting::create(['key' => 'test_null', 'value' => null, 'type' => 'null']);

            expect($setting->value)->toBeNull();
        });

        it('returns string value for string type', function () {
            $setting = Setting::create(['key' => 'test_str', 'value' => 'hello', 'type' => 'string']);

            expect($setting->value)->toBe('hello');
        });

        it('returns integer value for integer type', function () {
            $setting = Setting::create(['key' => 'test_int', 'value' => '42', 'type' => 'integer']);

            expect($setting->value)->toBe(42);
            expect($setting->value)->toBeInt();
        });

        it('returns float value for float type', function () {
            $setting = Setting::create(['key' => 'test_float', 'value' => '3.14', 'type' => 'float']);

            expect($setting->value)->toBe(3.14);
            expect($setting->value)->toBeFloat();
        });

        it('returns boolean value for boolean type', function () {
            $setting = Setting::create(['key' => 'test_bool', 'value' => '1', 'type' => 'boolean']);

            expect($setting->value)->toBeTrue();
            expect($setting->value)->toBeBool();
        });

        it('returns array for json type', function () {
            $setting = Setting::create(['key' => 'test_json', 'value' => '{"a":1,"b":2}', 'type' => 'json']);

            expect($setting->value)->toBe(['a' => 1, 'b' => 2]);
            expect($setting->value)->toBeArray();
        });

        it('returns decrypted string for encrypted type', function () {
            $setting = Setting::create(['key' => 'test_enc', 'value' => 'secret', 'type' => 'encrypted']);

            expect($setting->value)->toBe('secret');
        });
    });

    describe('set', function () {
        it('stores string value as string type', function () {
            $setting = Setting::create(['key' => 's', 'value' => 'text']);

            expect($setting->value)->toBe('text');
            expect($setting->type)->toBe('string');
        });

        it('stores integer value as integer type', function () {
            $setting = Setting::create(['key' => 'i', 'value' => 42]);

            expect($setting->value)->toBe(42);
            expect($setting->type)->toBe('integer');
        });

        it('stores float value as float type', function () {
            $setting = Setting::create(['key' => 'f', 'value' => 3.14]);

            expect($setting->value)->toBe(3.14);
            expect($setting->type)->toBe('float');
        });

        it('stores boolean value as boolean type', function () {
            $setting = Setting::create(['key' => 'b', 'value' => true]);

            expect($setting->value)->toBeTrue();
            expect($setting->type)->toBe('boolean');
        });

        it('stores null value as null type', function () {
            $setting = Setting::create(['key' => 'n', 'value' => null]);

            expect($setting->value)->toBeNull();
            expect($setting->type)->toBe('null');
        });

        it('stores array value as json type', function () {
            $setting = Setting::create(['key' => 'j', 'value' => ['x' => 1]]);

            expect($setting->value)->toBe(['x' => 1]);
            expect($setting->type)->toBe('json');
        });
    });
});
