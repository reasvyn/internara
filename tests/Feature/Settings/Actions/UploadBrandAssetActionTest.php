<?php

declare(strict_types=1);
use App\Settings\Branding\Actions\UploadBrandAssetAction;
use App\Settings\Models\Setting;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;

uses(LazilyRefreshDatabase::class);

test('upload brand asset creates ref record and returns url', function () {
    $file = UploadedFile::fake()->image('logo.png', 200, 200);
    $action = new UploadBrandAssetAction;

    $url = $action->execute($file, 'logo');

    expect($url)->toBeString();
    expect(Setting::where('key', 'brand_logo_ref')->exists())->toBeTrue();
});

test('upload brand asset reuses existing ref record', function () {
    Setting::create(['key' => 'brand_logo_ref', 'value' => null, 'type' => 'string']);
    $file = UploadedFile::fake()->image('logo.png', 200, 200);
    $action = new UploadBrandAssetAction;

    $url = $action->execute($file, 'logo');

    expect($url)->toBeString();
    expect(Setting::where('key', 'brand_logo_ref')->count())->toBe(1);
});

test('upload brand asset for favicon uses favicon collection', function () {
    $file = UploadedFile::fake()->image('favicon.png', 32, 32);
    $action = new UploadBrandAssetAction;

    $url = $action->execute($file, 'favicon');

    expect($url)->toBeString();
    expect(Setting::where('key', 'brand_favicon_ref')->exists())->toBeTrue();
});
