<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\AppInfo;
use Illuminate\Support\Facades\File;

beforeEach(function () {
    AppInfo::clearCache();
});

afterEach(function () {
    AppInfo::clearCache();
});

test('all returns metadata from app_info.json', function () {
    $data = AppInfo::all();

    expect($data)->toHaveKey('name')
        ->toHaveKey('version')
        ->toHaveKey('author')
        ->toHaveKey('license');
});

test('all returns empty array when file does not exist', function () {
    $path = base_path('app_info.json');
    $backup = File::get($path);

    File::move($path, base_path('app_info.json.backup'));
    AppInfo::clearCache();

    $data = AppInfo::all();

    expect($data)->toBeArray()->toBeEmpty();

    File::move(base_path('app_info.json.backup'), $path);
    AppInfo::clearCache();
});

test('get returns specific value by key', function () {
    expect(AppInfo::get('name'))->toBe('Internara');
});

test('get returns default when key does not exist', function () {
    expect(AppInfo::get('nonexistent', 'fallback'))->toBe('fallback');
});

test('get supports dot notation for nested keys', function () {
    expect(AppInfo::get('author.name'))->toBe('Reas Vyn');
    expect(AppInfo::get('author.email'))->toBe('reasvyn@gmail.com');
});

test('version returns version string', function () {
    expect(AppInfo::version())->toBeString()->not->toBeEmpty();
});

test('author returns author array', function () {
    $author = AppInfo::author();

    expect($author)->toHaveKey('name')
        ->toHaveKey('email')
        ->toHaveKey('github');
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
