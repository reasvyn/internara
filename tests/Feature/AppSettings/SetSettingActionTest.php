<?php

declare(strict_types=1);

use App\Actions\Admin\BatchSetSettingAction;
use App\Actions\Admin\SetSettingAction;
use App\Models\Setting;
use App\Support\Settings;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    Settings::clearOverrides();
    Cache::clear();
});

describe('execute', function () {
    it('creates a new setting with auto-detected type from string', function () {
        $action = app(SetSettingAction::class);

        $setting = $action->execute('my_key', 'hello');

        expect($setting)->toBeInstanceOf(Setting::class);
        expect($setting->key)->toBe('my_key');
        expect($setting->value)->toBe('hello');
        expect($setting->type)->toBe('string');
        expect($setting->group)->toBe('general');
    });

    it('auto-detects boolean type', function () {
        $action = app(SetSettingAction::class);

        $setting = $action->execute('flag', true);

        expect($setting->type)->toBe('boolean');
        expect($setting->value)->toBe(true);
    });

    it('auto-detects integer type', function () {
        $action = app(SetSettingAction::class);

        $setting = $action->execute('count', 42);

        expect($setting->type)->toBe('integer');
        expect($setting->value)->toBe(42);
    });

    it('auto-detects float type', function () {
        $action = app(SetSettingAction::class);

        $setting = $action->execute('ratio', 3.14);

        expect($setting->type)->toBe('float');
        expect($setting->value)->toBe(3.14);
    });

    it('auto-detects json type for arrays', function () {
        $action = app(SetSettingAction::class);

        $setting = $action->execute('items', ['a', 'b']);

        expect($setting->type)->toBe('json');
        expect($setting->value)->toBe(['a', 'b']);
    });

    it('accepts explicit type override', function () {
        $action = app(SetSettingAction::class);

        $setting = $action->execute('explicit', '123', type: 'integer');

        expect($setting->type)->toBe('integer');
        expect($setting->value)->toBe(123);
    });

    it('stores group and description', function () {
        $action = app(SetSettingAction::class);

        $setting = $action->execute(
            key: 'brand_name',
            value: 'Internara',
            group: 'branding',
            description: 'The application brand name',
        );

        expect($setting->group)->toBe('branding');
        expect($setting->description)->toBe('The application brand name');
    });

    it('updates an existing setting', function () {
        Setting::factory()->create(['key' => 'update_me', 'value' => 'old']);
        $action = app(SetSettingAction::class);

        $setting = $action->execute('update_me', 'new');

        expect($setting->value)->toBe('new');
    });

    it('invalidates the settings cache after update', function () {
        Setting::factory()->create(['key' => 'cached_val', 'value' => 'before']);
        Settings::get('cached_val');
        $action = app(SetSettingAction::class);

        $action->execute('cached_val', 'after_update');

        expect(Settings::get('cached_val'))->toBe('after_update');
    });
});

describe('executeBatch', function () {
    it('creates multiple settings from simple key-value pairs', function () {
        $results = app(BatchSetSettingAction::class)->execute([
            'key_one' => 'value_one',
            'key_two' => 'value_two',
        ]);

        expect($results)->toHaveCount(2)
            ->and($results)->toBeInstanceOf(Collection::class);
        expect(Setting::byKey('key_one')->exists())->toBeTrue();
        expect(Setting::byKey('key_two')->exists())->toBeTrue();
        expect(Settings::get('key_one'))->toBe('value_one');
    });

    it('creates settings with metadata from array values', function () {
        $results = app(BatchSetSettingAction::class)->execute([
            'db_host' => [
                'value' => 'localhost',
                'group' => 'database',
                'description' => 'Database hostname',
            ],
            'db_port' => [
                'value' => 3306,
                'group' => 'database',
                'type' => 'integer',
            ],
        ]);

        expect($results)->toHaveCount(2);

        $host = Setting::byKey('db_host')->first();
        expect($host->value)->toBe('localhost')
            ->and($host->group)->toBe('database');

        $port = Setting::byKey('db_port')->first();
        expect($port->value)->toBe(3306)
            ->and($port->type)->toBe('integer');
    });

    it('returns collection of Setting models', function () {
        $results = app(BatchSetSettingAction::class)->execute(['k1' => 'v1']);

        expect($results)->toBeInstanceOf(Collection::class)
            ->and($results->first())->toBeInstanceOf(Setting::class);
    });
});
