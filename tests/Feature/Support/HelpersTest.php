<?php

declare(strict_types=1);

use App\SysAdmin\Setting\Models\Setting;
use App\SysAdmin\Setting\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

test('setting helper retrieves value from database setting record in integrated context', function () {
    // Create a setting record in database
    Setting::create([
        'key' => 'site_title',
        'value' => 'Test Institution Title',
        'type' => 'string',
        'group' => 'general',
    ]);

    // Clear static state of Settings cache
    Cache::flush();

    // Resolve setting
    $value = setting('site_title');

    expect($value)->toBe('Test Institution Title');
});

test('brand helper falls back to Composer metadata if database settings are empty', function () {
    expect(brand('version'))->toBe(app_info('version'));
});
