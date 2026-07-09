<?php

declare(strict_types=1);

use App\Settings\Branding\Actions\RemoveBrandAssetAction;
use App\Settings\Models\Setting;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('removes brand asset and clears setting value', function () {
    Setting::create([
        'key' => 'brand_logo_ref',
        'value' => 'some-uuid',
        'type' => 'string',
    ]);

    app(RemoveBrandAssetAction::class)->execute('logo');

    $setting = Setting::where('key', 'brand_logo')->first();
    expect($setting)->not->toBeNull();
    expect($setting->value)->toBe('');
});

test('removes favicon asset and clears setting value', function () {
    Setting::create([
        'key' => 'brand_favicon_ref',
        'value' => 'some-uuid',
        'type' => 'string',
    ]);

    app(RemoveBrandAssetAction::class)->execute('favicon');

    $setting = Setting::where('key', 'site_favicon')->first();
    expect($setting)->not->toBeNull();
    expect($setting->value)->toBe('');
});

test('creates setting record when it does not exist', function () {
    app(RemoveBrandAssetAction::class)->execute('logo');

    $setting = Setting::where('key', 'brand_logo')->first();
    expect($setting)->not->toBeNull();
    expect($setting->value)->toBe('');
});
