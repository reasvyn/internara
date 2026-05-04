<?php

declare(strict_types=1);

namespace Tests\Unit\Core;

use App\Domain\Core\Support\AppInfo;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

beforeEach(function () {
    AppInfo::clearCache();
});

afterEach(function () {
    AppInfo::clearCache();
});

test('logo returns asset path by default', function () {
    expect(AppInfo::logo())->toContain('brand/logo.png');
});

test('get uses AppInfo mapped keys for settings', function () {
    AppInfo::clearCache();

    $name = AppInfo::get('name');

    expect($name)->toBeString()->not->toBeEmpty();
});

test('all returns cached instance', function () {
    $first = AppInfo::all();
    $second = AppInfo::all();

    expect($first)->toBe($second);
});

test('clearCache allows reloading data', function () {
    $first = AppInfo::all();

    AppInfo::clearCache();

    $second = AppInfo::all();

    expect($first)->toHaveKey('name')
        ->and($second)->toHaveKey('name');
});

test('all returns empty array when both app_info.json and composer.json are missing', function () {
    File::partialMock()
        ->shouldReceive('exists')
        ->with(base_path('app_info.json'))
        ->andReturn(false);

    File::partialMock()
        ->shouldReceive('exists')
        ->with(base_path('composer.json'))
        ->andReturn(false);

    $data = AppInfo::all();

    expect($data)->toBeArray()->toBeEmpty();
});

test('all logs error and returns empty array on JSON parse error', function () {
    Log::shouldReceive('error')
        ->once()
        ->with('Failed to parse JSON metadata file', \Mockery::type('array'));

    File::partialMock()
        ->shouldReceive('exists')
        ->with(base_path('app_info.json'))
        ->andReturn(true);

    File::partialMock()
        ->shouldReceive('get')
        ->with(base_path('app_info.json'))
        ->andReturn('{invalid json');

    $data = AppInfo::all();

    expect($data)->toBeArray()->toBeEmpty();
});

test('all logs error and returns empty array on file read exception', function () {
    Log::shouldReceive('error')
        ->once()
        ->with('Failed to read application metadata file', \Mockery::type('array'));

    File::partialMock()
        ->shouldReceive('exists')
        ->with(base_path('app_info.json'))
        ->andReturn(true);

    File::partialMock()
        ->shouldReceive('get')
        ->with(base_path('app_info.json'))
        ->andThrow(new \RuntimeException('File read failed'));

    $data = AppInfo::all();

    expect($data)->toBeArray()->toBeEmpty();
});

test('all falls back to composer.json when app_info.json is missing', function () {
    File::partialMock()
        ->shouldReceive('exists')
        ->with(base_path('app_info.json'))
        ->andReturn(false);

    $data = AppInfo::all();

    expect($data)->toHaveKey('name')
        ->and($data['name'])->toBe('reasvyn/internara');
});
