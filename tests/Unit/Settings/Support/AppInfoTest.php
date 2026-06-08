<?php

declare(strict_types=1);

use App\Settings\Support\AppInfo;

test('app info returns array from all', function () {
    $info = AppInfo::all();

    expect($info)->toBeArray();
    expect($info)->toHaveKeys(['name', 'version', 'description', 'license', 'author', 'support']);
});

test('app info get returns specific key', function () {
    $name = AppInfo::get('name');

    expect($name)->toBeString()->not->toBeEmpty();
});

test('app info get returns default for missing key', function () {
    expect(AppInfo::get('nonexistent', 'fallback'))->toBe('fallback');
});

test('app info version returns string', function () {
    $version = AppInfo::version();

    expect($version)->toBeString()->not->toBeEmpty();
});

test('app info author returns array', function () {
    $author = AppInfo::author();

    expect($author)->toBeArray();
});

test('app info clear cache works', function () {
    AppInfo::all();

    AppInfo::clearCache();

    expect(AppInfo::get('name'))->toBeString();
});
