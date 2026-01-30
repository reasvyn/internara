<?php

declare(strict_types=1);

namespace Modules\Setting\Tests\Unit\Services;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Modules\Setting\Models\Setting;
use Modules\Setting\Services\SettingService;

uses(RefreshDatabase::class);

test('it can set and get a setting value', function () {
    $service = new SettingService(new Setting);

    $service->setValue('site_title', 'Internara');

    expect($service->getValue('site_title'))->toBe('Internara');
    $this->assertDatabaseHas('settings', ['key' => 'site_title', 'value' => 'Internara']);
});

test('it returns default value if setting not found', function () {
    $service = new SettingService(new Setting);

    expect($service->getValue('non_existent', 'Default'))->toBe('Default');
});

test('it caches setting values', function () {
    $service = new SettingService(new Setting);
    $service->setValue('cached_key', 'Original');

    // First call caches it
    $service->getValue('cached_key');

    // Manually change database without clearing cache
    Setting::where('key', 'cached_key')->update(['value' => 'Changed']);

    expect($service->getValue('cached_key'))->toBe('Original');

    // Skip cache should get new value
    expect($service->getValue('cached_key', skipCache: true))->toBe('Changed');
});

test('it clears cache when value is updated', function () {
    $service = new SettingService(new Setting);
    $service->setValue('update_key', 'Old');

    $service->getValue('update_key'); // Put in cache

    $service->setValue('update_key', 'New');

    expect($service->getValue('update_key'))->toBe('New');
});

test('it can retrieve settings by group', function () {
    $service = new SettingService(new Setting);

    $service->setValue('key1', 'v1', ['group' => 'groupA']);
    $service->setValue('key2', 'v2', ['group' => 'groupA']);
    $service->setValue('key3', 'v3', ['group' => 'groupB']);

    $groupA = $service->group('groupA');

    expect($groupA)
        ->toHaveCount(2)
        ->and($groupA->pluck('key')->toArray())
        ->toContain('key1', 'key2');
});
