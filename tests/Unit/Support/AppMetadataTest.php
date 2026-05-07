<?php

declare(strict_types=1);

use App\Support\AppInfo;
use App\Support\AppMetadata;
use App\Support\Settings;

beforeEach(function () {
    AppInfo::clearCache();
    Settings::clearOverrides();
    Cache::clear();
});

it('can get app name', function () {
    $name = AppMetadata::appName();

    expect($name)->toBeString();
    expect($name)->toBe('Internara');
});

it('returns app name when not installed', function () {
    // Mock not installed state
    Settings::override(['brand_name' => null]);

    $brandName = AppMetadata::brandName();

    expect($brandName)->toBeString();
});

it('can get site title', function () {
    $title = AppMetadata::siteTitle();

    expect($title)->toBeString();
});

it('can get app logo', function () {
    $logo = AppMetadata::appLogo();

    expect($logo)->toBeString();
    expect($logo)->toContain('logo.png');
});

it('can get version', function () {
    $version = AppMetadata::version();

    expect($version)->toBeString();
});

it('can get author name', function () {
    $authorName = AppMetadata::authorName();

    expect($authorName)->toBeString();
    expect($authorName)->toBe('Reas Vyn');
});

it('can get author email', function () {
    $email = AppMetadata::authorEmail();

    expect($email)->toBeString();
    expect($email)->toBe('reasvyn@gmail.com');
});

it('can get license', function () {
    $license = AppMetadata::license();

    expect($license)->toBe('MIT');
});

it('can get description', function () {
    $description = AppMetadata::description();

    expect($description)->toBeString();
    expect($description)->toContain('field work management system');
});

it('can get value by key using get method', function () {
    $name = AppMetadata::get('name');

    expect($name)->toBeString();
});

it('returns default when key not found in get method', function () {
    $value = AppMetadata::get('non_existent', 'default');
    expect($value)->toBe('default');
});

it('can get colors with defaults', function () {
    $colors = AppMetadata::colors();

    expect($colors)->toBeArray();
    expect($colors)->toHaveKeys(['primary', 'secondary', 'accent']);
});
