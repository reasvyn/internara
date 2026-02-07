<?php

declare(strict_types=1);

namespace Modules\Setting\Tests\Unit\Services;

use Illuminate\Support\Facades\Config;
use Modules\Core\Services\Contracts\MetadataService;
use Modules\Setting\Models\Setting;
use Modules\Setting\Services\SettingService;

test('it prioritizes metadata service for app identity', function () {
    $metadata = mock(MetadataService::class);
    $metadata->shouldReceive('verifyIntegrity')->once();
    $metadata->shouldReceive('get')->with('name')->andReturn('Internara SSoT');

    $service = new SettingService(mock(Setting::class), $metadata);

    expect($service->getValue('app_name'))->toBe('Internara SSoT');
});

test('it falls back to database for other keys', function () {
    $metadata = mock(MetadataService::class);
    $metadata->shouldReceive('verifyIntegrity')->once();
    $metadata->shouldReceive('get')->andReturn(null);

    $settingModel = mock(Setting::class);
    $settingModel
        ->shouldReceive('find')
        ->with('custom_key')
        ->andReturn((object) ['value' => 'db-value']);

    $service = new SettingService($settingModel, $metadata);

    expect($service->getValue('custom_key'))->toBe('db-value');
});

test('it falls back to config if not in db', function () {
    $metadata = mock(MetadataService::class);
    $metadata->shouldReceive('verifyIntegrity')->once();
    $metadata->shouldReceive('get')->andReturn(null);

    $settingModel = mock(Setting::class);
    $settingModel->shouldReceive('find')->andReturn(null);

    Config::set('my.config', 'config-value');

    $service = new SettingService($settingModel, $metadata);

    expect($service->getValue('my.config'))->toBe('config-value');
});
