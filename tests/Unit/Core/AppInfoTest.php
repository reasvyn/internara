<?php

declare(strict_types=1);

namespace Tests\Unit\Core;

use App\Domain\Core\Support\AppInfo;

beforeEach(function () {
    AppInfo::clearCache();
});

afterEach(function () {
    AppInfo::clearCache();
});

test('all returns metadata from composer.json', function () {
    $data = AppInfo::all();

    expect($data)
        ->toHaveKey('name')
        ->toHaveKey('version')
        ->toHaveKey('author')
        ->toHaveKey('license')
        ->toHaveKey('support');
});

test('get returns specific value by key', function () {
    expect(AppInfo::get('name'))->toBe('internara');
});

test('get returns default when key does not exist', function () {
    expect(AppInfo::get('nonexistent', 'fallback'))->toBe('fallback');
});

test('get supports dot notation for nested keys', function () {
    expect(AppInfo::get('author.name'))->toBe('Reas Vyn');
    expect(AppInfo::get('author.email'))->toBe('reasvyn@gmail.com');
    expect(AppInfo::get('support.email'))->toBe('reasvyn@gmail.com');
});

test('version returns version string', function () {
    expect(AppInfo::version())->toBe('0.1.0');
});

test('author returns author array', function () {
    $author = AppInfo::author();

    expect($author)->toHaveKey('name')->toHaveKey('email')->toHaveKey('github');
});

test('description is available', function () {
    expect(AppInfo::get('description'))->not->toBeEmpty();
});

test('license is available', function () {
    expect(AppInfo::get('license'))->toBe('MIT');
});

test('clearCache resets cached data', function () {
    $first = AppInfo::all();

    AppInfo::clearCache();

    $second = AppInfo::all();

    expect($first)->toBe($second);
});

test('caching works (same instance returned on subsequent calls)', function () {
    $first = AppInfo::all();
    $second = AppInfo::all();

    expect($first)->toBe($second);
});
