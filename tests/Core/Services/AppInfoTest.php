<?php

declare(strict_types=1);

use App\Core\Services\AppInfo;
use Illuminate\Support\Facades\Cache;

it('FR-SUP4: all() extracts composer.json metadata with the expected shape', function () {
    $meta = AppInfo::all();

    expect($meta)->toHaveKeys(['name', 'version', 'description', 'license', 'author', 'support', 'gitUrl']);
    expect($meta['name'])->toBe('Internara');
    expect($meta['version'])->toBe('0.14.0');
    expect($meta['author']['name'])->toBe('Reas Vyn');
});

it('FR-SUP4: get() resolves keys through dot notation', function () {
    expect(AppInfo::get('author.name'))->toBe('Reas Vyn');
    expect(AppInfo::get('missing', 'fallback'))->toBe('fallback');
});

it('FR-SUP4: name() and version() prefer config over composer metadata', function () {
    config()->set('app.name', 'Test App');
    config()->set('app.version', '9.9.9');

    expect(AppInfo::name())->toBe('Test App');
    expect(AppInfo::version())->toBe('9.9.9');
});

it('FR-SUP4: author accessors expose the composer author details', function () {
    expect(AppInfo::authorName())->toBe('Reas Vyn');
    expect(AppInfo::authorEmail())->toBe('reasvyn@gmail.com');
    expect(AppInfo::gitUrl())->toBe('https://github.com/reasvyn');
});

it('FR-SUP8: clearCache() resets the cached metadata and reloads cleanly', function () {
    AppInfo::clearCache();

    expect(AppInfo::all())->toHaveKey('name');
    expect(AppInfo::all()['name'])->toBe('Internara');
});

it('FR-SUP8: metadata is persisted in the configured cache store', function () {
    Cache::store('array')->forget(config('cache-keys.appinfo_metadata'));
    (new ReflectionClass(AppInfo::class))->setStaticPropertyValue('metadata', null);

    AppInfo::all();

    expect(Cache::store('array')->has(config('cache-keys.appinfo_metadata')))->toBeTrue();
});
