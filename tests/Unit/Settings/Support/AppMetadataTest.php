<?php

declare(strict_types=1);

use App\Settings\Support\AppMetadata;

test('app name returns string', function () {
    expect(AppMetadata::appName())->toBeString()->not->toBeEmpty();
});

test('brand name falls back to app name', function () {
    expect(AppMetadata::brandName())->toBeString();
});

test('site title returns string', function () {
    expect(AppMetadata::siteTitle())->toBeString();
});

test('app logo returns asset url', function () {
    expect(AppMetadata::appLogo())->toContain('/brand/logo.png');
});

test('version returns string', function () {
    expect(AppMetadata::version())->toBeString();
});

test('author name returns string', function () {
    expect(AppMetadata::authorName())->toBeString();
});

test('author email returns string', function () {
    expect(AppMetadata::authorEmail())->toBeString();
});

test('description returns string', function () {
    expect(AppMetadata::description())->toBeString();
});

test('license returns string', function () {
    expect(AppMetadata::license())->toBeString();
});

test('colors returns array with correct keys', function () {
    $colors = AppMetadata::colors();

    expect($colors)->toHaveKeys(['primary', 'secondary', 'accent', 'base', 'content']);
});

test('get routes known keys', function () {
    expect(AppMetadata::get('name'))->toBeString();
    expect(AppMetadata::get('app_name'))->toBeString();
    expect(AppMetadata::get('version'))->toBeString();
});

test('get returns default for unknown key', function () {
    expect(AppMetadata::get('nonexistent', 'fallback'))->toBe('fallback');
});
