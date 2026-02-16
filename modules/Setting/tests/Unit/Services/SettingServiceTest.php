<?php

declare(strict_types=1);

namespace Modules\Setting\Tests\Unit\Services;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Modules\Core\Metadata\Services\Contracts\MetadataService;
use Modules\Setting\Models\Setting;
use Modules\Setting\Services\SettingService;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Ensure cache driver is 'array' for unit tests to avoid missing table issues
    Config::set('cache.default', 'array');
});

test('it prioritizes metadata service for app identity', function () {
    $metadata = mock(MetadataService::class);
    $metadata->shouldReceive('verifyIntegrity')->once();
    $metadata->shouldReceive('get')->with('name')->andReturn('Internara SSoT');

    $service = new SettingService(new Setting(), $metadata);

    expect($service->getValue('app_name'))->toBe('Internara SSoT');
});

test('it falls back to database for other keys', function () {
    $metadata = mock(MetadataService::class);
    $metadata->shouldReceive('verifyIntegrity')->once();
    $metadata->shouldReceive('get')->andReturn(null);

    // Using real database record via factory or direct create
    Setting::create([
        'key' => 'custom_key',
        'value' => 'db-value',
        'type' => 'string',
    ]);

    $service = new SettingService(new Setting(), $metadata);

    expect($service->getValue('custom_key'))->toBe('db-value');
});

test('it falls back to config if not in db', function () {
    $metadata = mock(MetadataService::class);
    $metadata->shouldReceive('verifyIntegrity')->once();
    $metadata->shouldReceive('get')->andReturn(null);

    Config::set('my.config', 'config-value');

    $service = new SettingService(new Setting(), $metadata);

    expect($service->getValue('my.config'))->toBe('config-value');
});
