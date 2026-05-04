<?php

declare(strict_types=1);

namespace Tests\Unit\Core;

use App\Domain\Core\Support\AppInfo;
use App\Domain\Core\Support\AppMetadata;
use App\Domain\Core\Support\Settings;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

beforeEach(function () {
    AppInfo::clearCache();
    Settings::clearOverrides();
});

test('appName returns AppInfo name', function () {
    expect(AppMetadata::appName())->toBeString()->not->toBeEmpty();
});

test('brandName returns settings value when installed', function () {
    File::partialMock()
        ->shouldReceive('exists')
        ->with(storage_path('app/.installed'))
        ->andReturn(true);

    Settings::override(['brand_name' => 'Custom Brand']);

    expect(AppMetadata::brandName())->toBe('Custom Brand');
});

test('siteTitle returns settings value when installed', function () {
    File::partialMock()
        ->shouldReceive('exists')
        ->with(storage_path('app/.installed'))
        ->andReturn(true);

    Settings::override(['site_title' => 'Custom Title']);

    expect(AppMetadata::siteTitle())->toBe('Custom Title');
});

test('appLogo returns asset path', function () {
    expect(AppMetadata::appLogo())->toContain('brand/logo.png');
});

test('brandLogo returns settings value when installed', function () {
    File::partialMock()
        ->shouldReceive('exists')
        ->with(storage_path('app/.installed'))
        ->andReturn(true);

    Settings::override(['brand_logo' => 'https://example.com/logo.png']);

    expect(AppMetadata::brandLogo())->toBe('https://example.com/logo.png');
});

test('favicon returns settings favicon when set', function () {
    File::partialMock()
        ->shouldReceive('exists')
        ->with(storage_path('app/.installed'))
        ->andReturn(true);

    Settings::override(['site_favicon' => 'https://example.com/favicon.ico']);

    expect(AppMetadata::favicon())->toBe('https://example.com/favicon.ico');
});

test('colors returns default values', function () {
    $colors = AppMetadata::colors();

    expect($colors)
        ->toHaveKey('primary', '#0ea5e9')
        ->toHaveKey('secondary', '#64748b')
        ->toHaveKey('accent', '#f59e0b');
});

test('colors returns settings values when installed', function () {
    File::partialMock()
        ->shouldReceive('exists')
        ->with(storage_path('app/.installed'))
        ->andReturn(true);

    Settings::override([
        'primary_color' => '#ff0000',
        'secondary_color' => '#00ff00',
        'accent_color' => '#0000ff',
    ]);

    $colors = AppMetadata::colors();

    expect($colors)
        ->toHaveKey('primary', '#ff0000')
        ->toHaveKey('secondary', '#00ff00')
        ->toHaveKey('accent', '#0000ff');
});

test('version returns AppInfo version', function () {
    expect(AppMetadata::version())->toBe(AppInfo::version());
});

test('authorName returns author name from AppInfo', function () {
    expect(AppMetadata::authorName())->toBeString();
});

test('authorEmail returns author email from AppInfo', function () {
    expect(AppMetadata::authorEmail())->toBeString();
});

test('description returns AppInfo description', function () {
    expect(AppMetadata::description())->toBeString();
});

test('license returns AppInfo license', function () {
    expect(AppMetadata::license())->toBe('MIT');
});

test('get delegates to mapped functions', function () {
    expect(AppMetadata::get('name'))->toBeString()
        ->and(AppMetadata::get('version'))->toBeString()
        ->and(AppMetadata::get('logo'))->toBeString()
        ->and(AppMetadata::get('colors'))->toBeArray();
});

test('get falls back to AppInfo for unmapped keys', function () {
    expect(AppMetadata::get('author.name'))->toBe(AppInfo::get('author.name'));
});

test('get returns default for unknown AppInfo key', function () {
    expect(AppMetadata::get('unknown.key', 'fallback'))->toBe('fallback');
});

test('appMetadata class is final', function () {
    $reflection = new \ReflectionClass(AppMetadata::class);

    expect($reflection->isFinal())->toBeTrue();
});

test('brandName falls back to appName when not installed', function () {
    File::partialMock()
        ->shouldReceive('exists')
        ->with(storage_path('app/.installed'))
        ->andReturn(false);

    expect(AppMetadata::brandName())->toBe(AppMetadata::appName());
});

test('brandName returns appName when brand_name is not a string', function () {
    File::partialMock()
        ->shouldReceive('exists')
        ->with(storage_path('app/.installed'))
        ->andReturn(true);

    Settings::override(['brand_name' => 123]);

    expect(AppMetadata::brandName())->toBe(AppMetadata::appName());
});

test('siteTitle shows setup suffix when not installed', function () {
    File::partialMock()
        ->shouldReceive('exists')
        ->with(storage_path('app/.installed'))
        ->andReturn(false);

    expect(AppMetadata::siteTitle())->toBe(AppMetadata::appName().' - Setup');
});

test('siteTitle returns brandName when site_title is not a string', function () {
    File::partialMock()
        ->shouldReceive('exists')
        ->with(storage_path('app/.installed'))
        ->andReturn(true);

    Settings::override(['site_title' => 456]);

    expect(AppMetadata::siteTitle())->toBe(AppMetadata::brandName());
});

test('brandLogo returns default logo when not installed', function () {
    File::partialMock()
        ->shouldReceive('exists')
        ->with(storage_path('app/.installed'))
        ->andReturn(false);

    expect(AppMetadata::brandLogo())->toBe(AppMetadata::appLogo());
});

test('brandLogo returns default logo when brand_logo is empty', function () {
    File::partialMock()
        ->shouldReceive('exists')
        ->with(storage_path('app/.installed'))
        ->andReturn(true);

    Settings::override(['brand_logo' => '']);

    expect(AppMetadata::brandLogo())->toBe(AppMetadata::appLogo());
});

test('favicon returns default when not installed', function () {
    File::partialMock()
        ->shouldReceive('exists')
        ->with(storage_path('app/.installed'))
        ->andReturn(false);

    expect(AppMetadata::favicon())->toContain('favicon.ico');
});

test('favicon falls back to brandLogo when site_favicon is empty', function () {
    File::partialMock()
        ->shouldReceive('exists')
        ->with(storage_path('app/.installed'))
        ->andReturn(true);

    Settings::override([
        'site_favicon' => '',
        'brand_logo' => 'https://example.com/logo.png',
    ]);

    expect(AppMetadata::favicon())->toBe('https://example.com/logo.png');
});

test('favicon returns default favicon when site_favicon is empty string', function () {
    File::partialMock()
        ->shouldReceive('exists')
        ->with(storage_path('app/.installed'))
        ->andReturn(true);

    Settings::override([
        'site_favicon' => '',
        'brand_logo' => '',
    ]);

    expect(AppMetadata::favicon())->toContain('favicon.ico');
});

test('colors returns override values when installed', function () {
    File::partialMock()
        ->shouldReceive('exists')
        ->with(storage_path('app/.installed'))
        ->andReturn(true);

    Settings::override([
        'primary_color' => '#ff0000',
        'secondary_color' => '#00ff00',
        'accent_color' => '#0000ff',
    ]);

    $colors = AppMetadata::colors();

    expect($colors)
        ->toHaveKey('primary', '#ff0000')
        ->toHaveKey('secondary', '#00ff00')
        ->toHaveKey('accent', '#0000ff');
});

test('isInstalled returns false on exception', function () {
    File::partialMock()
        ->shouldReceive('exists')
        ->with(storage_path('app/.installed'))
        ->andThrow(new \RuntimeException('Storage unavailable'));

    Log::shouldReceive('warning')
        ->once()
        ->with('Failed to check installation status', \Mockery::type('array'));

    expect(AppMetadata::brandName())->toBe(AppMetadata::appName());
});
