<?php

declare(strict_types=1);

namespace Modules\Setting\Tests\Unit\Services;


use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Modules\Setting\Models\Setting;
use Modules\Setting\Services\SettingService;
use Modules\Shared\Support\AppInfo;



beforeEach(function () {
    // Ensure cache driver is 'array' for unit tests to avoid missing table issues
    Config::set('cache.default', 'array');

    $this->metadataPath = base_path('app_info.json');
    $this->originalContent = File::exists($this->metadataPath)
        ? File::get($this->metadataPath)
        : null;

    AppInfo::clearCache();
});

afterEach(function () {
    if ($this->originalContent) {
        File::put($this->metadataPath, $this->originalContent);
    } else {
        File::delete($this->metadataPath);
    }
    AppInfo::clearCache();
});

test('it prioritizes metadata service for app identity', function () {
    File::put($this->metadataPath, json_encode(['name' => 'Internara SSoT']));
    AppInfo::clearCache();

    $service = new SettingService(new Setting);

    expect($service->getValue('app_name'))->toBe('Internara SSoT');
});

test('it falls back to database for other keys', function () {
    // Using real database record via factory or direct create
    Setting::create([
        'key' => 'custom_key',
        'value' => 'db-value',
        'type' => 'string',
    ]);

    $service = new SettingService(new Setting);

    expect($service->getValue('custom_key'))->toBe('db-value');
});

test('it falls back to config if not in db', function () {
    Config::set('my.config', 'config-value');

    $service = new SettingService(new Setting);

    expect($service->getValue('my.config'))->toBe('config-value');
});
