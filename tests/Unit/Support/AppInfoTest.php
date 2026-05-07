<?php

declare(strict_types=1);

use App\Support\AppInfo;

beforeEach(function () {
    AppInfo::clearCache();
});

it('can get all info from composer.json', function () {
    $info = AppInfo::all();

    expect($info)->toBeArray();
    expect($info)->toHaveKey('name');
    expect($info['name'])->toBe('Internara');
});

it('can get specific key', function () {
    $name = AppInfo::get('name');

    expect($name)->toBe('Internara');
});

it('returns default when key not found', function () {
    $value = AppInfo::get('non_existent_key', 'default_value');

    expect($value)->toBe('default_value');
});

it('can get version', function () {
    $version = AppInfo::version();

    expect($version)->toBeString();
});

it('can get author info', function () {
    $author = AppInfo::author();

    expect($author)->toBeArray();
    expect($author)->toHaveKey('name');
});

it('can get logo url', function () {
    $logo = AppInfo::logo();

    expect($logo)->toBeString();
    expect($logo)->toContain('logo.png');
});

it('caches results after first call', function () {
    $first = AppInfo::all();
    $second = AppInfo::all();

    expect($first)->toBe($second);
});

it('can clear cache', function () {
    AppInfo::all();
    AppInfo::clearCache();

    // After clearing, should work fine
    $info = AppInfo::all();
    expect($info)->toBeArray();
});
