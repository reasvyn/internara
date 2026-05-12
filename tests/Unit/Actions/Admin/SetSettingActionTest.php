<?php

declare(strict_types=1);

use App\Actions\Admin\BatchSetSettingAction;
use App\Actions\Admin\SetSettingAction;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

describe('execute', function () {
    it('creates a new setting with string value', function () {
        $setting = app(SetSettingAction::class)->execute('site_name', 'Internara');

        expect($setting)->toBeInstanceOf(Setting::class)
            ->and($setting->key)->toBe('site_name')
            ->and($setting->value)->toBe('Internara')
            ->and($setting->type)->toBe('string')
            ->and($setting->group)->toBe('general');
    });

    it('auto-detects boolean type', function () {
        $setting = app(SetSettingAction::class)->execute('maintenance_mode', true);

        expect($setting->type)->toBe('boolean')
            ->and($setting->value)->toBeTrue();
    });

    it('auto-detects integer type', function () {
        $setting = app(SetSettingAction::class)->execute('max_users', 100);

        expect($setting->type)->toBe('integer')
            ->and($setting->value)->toBe(100);
    });

    it('auto-detects float type', function () {
        $setting = app(SetSettingAction::class)->execute('tax_rate', 0.11);

        expect($setting->type)->toBe('float')
            ->and($setting->value)->toBe(0.11);
    });

    it('auto-detects json type for arrays', function () {
        $setting = app(SetSettingAction::class)->execute('allowed_ips', ['192.168.1.1', '10.0.0.1']);

        expect($setting->type)->toBe('json')
            ->and($setting->value)->toBe(['192.168.1.1', '10.0.0.1']);
    });

    it('accepts explicit type override', function () {
        $setting = app(SetSettingAction::class)->execute('port', '8080', type: 'integer');

        expect($setting->type)->toBe('integer')
            ->and($setting->value)->toBe(8080);
    });

    it('stores group and description metadata', function () {
        $setting = app(SetSettingAction::class)->execute(
            key: 'app_logo',
            value: 'logo.png',
            group: 'branding',
            description: 'Main application logo filename',
        );

        expect($setting->group)->toBe('branding')
            ->and($setting->description)->toBe('Main application logo filename');
    });

    it('updates an existing setting', function () {
        Setting::factory()->create(['key' => 'theme_color', 'value' => 'blue']);

        $setting = app(SetSettingAction::class)->execute('theme_color', 'red');

        expect($setting->value)->toBe('red');
    });

    it('rejects invalid key format', function () {
        $action = app(SetSettingAction::class);

        expect(fn () => $action->execute('Invalid-Key!', 'value'))
            ->toThrow(ValidationException::class);
    });

    it('rejects empty key', function () {
        $action = app(SetSettingAction::class);

        expect(fn () => $action->execute('', 'value'))
            ->toThrow(ValidationException::class);
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
