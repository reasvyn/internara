<?php

declare(strict_types=1);

use App\Modules\Settings\Actions\SetSettingAction;
use App\Modules\Settings\Data\SettingData;
use App\Modules\Settings\Models\Setting;
use App\Modules\Settings\Services\Settings;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| YB22J — Settings Infrastructure — SetSettingAction (spec-driven)
|--------------------------------------------------------------------------
*/

beforeEach(function () {
    Settings::clearOverrides();
    Cache::flush();
});

describe('YB22J: SetSettingAction', function (): void {
    test('YB22J-FR-S1: persists a setting using string key as primary key', function () {
        $setting = app(SetSettingAction::class)->execute(
            new SettingData(key: 'test.simple', value: 'hello'),
        );

        $this->assertModelExists($setting);
        expect(Setting::find('test.simple'))->not->toBeNull()
            ->and(Setting::find('test.simple')->value)->toBe('hello');
    });

    test('YB22J-FR-S3: rejects uppercase key pattern via ValidSettingKey', function () {
        expect(fn () => app(SetSettingAction::class)->execute(
            new SettingData(key: 'VALID', value: 'x'),
        ))->toThrow(ValidationException::class);

        expect(Setting::where('key', 'VALID')->exists())->toBeFalse();
    });

    test('YB22J-FR-S3: rejects key containing space via ValidSettingKey', function () {
        expect(fn () => app(SetSettingAction::class)->execute(
            new SettingData(key: 'bad key', value: 'x'),
        ))->toThrow(ValidationException::class);
    });

    test('YB22J-NFR-S2: accepts valid dotted lowercase key pattern', function () {
        $setting = app(SetSettingAction::class)->execute(
            new SettingData(key: 'valid.key', value: 'ok'),
        );

        $this->assertModelExists($setting);
        expect(Setting::find('valid.key')->value)->toBe('ok');
    });

    test('YB22J-FR-S3: auto-detects integer type', function () {
        app(SetSettingAction::class)->execute(new SettingData(key: 'test.int', value: 42));

        $setting = Setting::find('test.int');
        expect($setting->type)->toBe('integer')
            ->and($setting->value)->toBe(42);
    });

    test('YB22J-FR-S3: auto-detects boolean type', function () {
        app(SetSettingAction::class)->execute(new SettingData(key: 'test.bool', value: true));

        $setting = Setting::find('test.bool');
        expect($setting->type)->toBe('boolean')
            ->and($setting->value)->toBeTrue();
    });

    test('YB22J-FR-S3: auto-detects json type for arrays', function () {
        app(SetSettingAction::class)->execute(
            new SettingData(key: 'test.json', value: ['a' => 1]),
        );

        $setting = Setting::find('test.json');
        expect($setting->type)->toBe('json')
            ->and($setting->value)->toBe(['a' => 1]);
    });

    test('YB22J-FR-S10: auto-detects float type', function () {
        app(SetSettingAction::class)->execute(new SettingData(key: 'test.float', value: 1.5));

        $setting = Setting::find('test.float');
        expect($setting->type)->toBe('float')
            ->and($setting->value)->toBe(1.5);
    });

    test('YB22J-FR-S10: auto-detects string type by default', function () {
        app(SetSettingAction::class)->execute(new SettingData(key: 'test.str', value: 'text'));

        expect(Setting::find('test.str')->type)->toBe('string');
    });

    test('YB22J-FR-C1: invalidates settings_key cache after set', function () {
        Cache::put(config('cache-keys.settings_key').'test.cached', 'x');

        app(SetSettingAction::class)->execute(new SettingData(key: 'test.cached', value: 'new'));

        expect(Cache::has(config('cache-keys.settings_key').'test.cached'))->toBeFalse()
            ->and(Setting::find('test.cached')->value)->toBe('new');
    });

    test('YB22J-FR-S3: respects explicit type when provided', function () {
        app(SetSettingAction::class)->execute(
            new SettingData(key: 'test.explicit', value: 'raw', type: 'string'),
        );

        expect(Setting::find('test.explicit')->type)->toBe('string')
            ->and(Setting::find('test.explicit')->value)->toBe('raw');
    });
});
