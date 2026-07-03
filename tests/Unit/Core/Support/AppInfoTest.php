<?php

declare(strict_types=1);

use App\Core\Services\AppInfo;

beforeEach(function () {
    AppInfo::clearCache();
});

test('all returns array with project metadata', function () {
    $metadata = AppInfo::all();

    expect($metadata)->toBeArray();
    expect($metadata)->toHaveKey('name');
    expect($metadata)->toHaveKey('version');
    expect($metadata)->toHaveKey('description');
    expect($metadata)->toHaveKey('license');
    expect($metadata)->toHaveKey('author');
    expect($metadata)->toHaveKey('support');
    expect($metadata)->toHaveKey('gitUrl');
});

test('get returns specific key', function () {
    expect(AppInfo::get('name'))->toBe('Internara');
});

test('get returns default for missing key', function () {
    expect(AppInfo::get('nonexistent', 'fallback'))->toBe('fallback');
});

test('name returns application name', function () {
    expect(AppInfo::name())->toBe('Internara');
});

test('version returns project version', function () {
    expect(AppInfo::version())->not->toBeEmpty();
});

test('description returns project description', function () {
    $description = AppInfo::description();

    expect($description)->toContain('vocational fieldwork management');
});

test('license returns MIT', function () {
    expect(AppInfo::license())->toContain('MIT');
});

test('author returns author info array', function () {
    $author = AppInfo::author();

    expect($author)->toHaveKey('name');
    expect($author)->toHaveKey('email');
});

test('authorName returns author name', function () {
    expect(AppInfo::authorName())->toBe('Reas Vyn');
});

test('authorEmail returns author email', function () {
    expect(AppInfo::authorEmail())->toContain('@');
});

test('support returns support info', function () {
    $support = AppInfo::support();

    expect($support)->toHaveKey('email');
});

test('gitUrl returns repository url', function () {
    $url = AppInfo::gitUrl();

    expect($url)->toContain('github.com');
});

test('clearCache resets cached metadata', function () {
    AppInfo::all();
    AppInfo::clearCache();

    $ref = new ReflectionClass(AppInfo::class);
    $prop = $ref->getProperty('metadata');
    $prop->setAccessible(true);

    expect($prop->getValue())->toBeNull();
});

test('all is cached after first call', function () {
    $first = AppInfo::all();
    $second = AppInfo::all();

    expect($first)->toBe($second);
});
