<?php

declare(strict_types=1);

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
    it('creates multiple settings from simple values', function () {
        $action = app(SetSettingAction::class);

        $results = $action->executeBatch([
            'batch_key_1' => 'value1',
            'batch_key_2' => 'value2',
        ]);

        expect($results)->toHaveCount(2);
        expect(Setting::byKey('batch_key_1')->exists())->toBeTrue();
        expect(Setting::byKey('batch_key_2')->exists())->toBeTrue();
    });

    it('creates settings with metadata from array values', function () {
        $action = app(SetSettingAction::class);

        $results = $action->executeBatch([
            'mail_driver' => [
                'value' => 'smtp',
                'group' => 'mail',
                'description' => 'Mail driver',
            ],
            'mail_port' => [
                'value' => 587,
                'group' => 'mail',
                'type' => 'integer',
            ],
        ]);

        expect($results)->toHaveCount(2);

        $driver = Setting::byKey('mail_driver')->first();
        expect($driver->value)->toBe('smtp');
        expect($driver->group)->toBe('mail');

        $port = Setting::byKey('mail_port')->first();
        expect($port->value)->toBe(587);
        expect($port->type)->toBe('integer');
    });

    it('invalidates cache after batch update', function () {
        Setting::factory()->create(['key' => 'batch_cache', 'value' => 'old']);
        Settings::get('batch_cache');
        $action = app(SetSettingAction::class);

        $action->executeBatch(['batch_cache' => 'new']);

        expect(Settings::get('batch_cache'))->toBe('new');
    });

    it('returns collection of Setting models', function () {
        $action = app(SetSettingAction::class);

        $results = $action->executeBatch(['k1' => 'v1']);

        expect($results)->toBeInstanceOf(Collection::class);
        expect($results->first())->toBeInstanceOf(Setting::class);
    });
});
