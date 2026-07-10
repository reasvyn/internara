<?php

declare(strict_types=1);

use App\Settings\Support\Brand;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    Cache::flush();
});

test('name returns string', function () {
    expect(Brand::name())->toBeString()->not->toBeEmpty();
});

test('title returns string', function () {
    expect(Brand::title())->toBeString();
});

test('logo returns string', function () {
    expect(Brand::logo())->toBeString();
});

test('favicon returns string', function () {
    expect(Brand::favicon())->toBeString();
});

test('version returns string', function () {
    expect(Brand::version())->toBeString();
});

test('author name returns string', function () {
    expect(Brand::authorName())->toBeString();
});

test('author email returns string', function () {
    expect(Brand::authorEmail())->toBeString();
});

test('description returns string', function () {
    expect(Brand::description())->toBeString();
});

test('license returns string', function () {
    expect(Brand::license())->toBeString();
});

test('colors returns array with correct keys', function () {
    $colors = Brand::colors();

    expect($colors)->toHaveKeys(['primary', 'secondary', 'accent', 'base', 'content']);
});

test('get routes known keys', function () {
    expect(Brand::get('name'))->toBeString();
    expect(Brand::get('title'))->toBeString();
    expect(Brand::get('logo'))->toBeString();
    expect(Brand::get('favicon'))->toBeString();
    expect(Brand::get('version'))->toBeString();
    expect(Brand::get('colors'))->toBeArray();
    expect(Brand::get('author_name'))->toBeString();
    expect(Brand::get('author_email'))->toBeString();
    expect(Brand::get('description'))->toBeString();
    expect(Brand::get('license'))->toBeString();
    expect(Brand::get('gitUrl'))->toBeString();
});

test('get returns default for unknown key', function () {
    expect(Brand::get('nonexistent', 'fallback'))->toBe('fallback');
});

test('get returns Brand values for mapped keys', function () {
    expect(Brand::get('name'))->toBe(Brand::name());
    expect(Brand::get('title'))->toBe(Brand::title());
    expect(Brand::get('logo'))->toBe(Brand::logo());
    expect(Brand::get('favicon'))->toBe(Brand::favicon());
    expect(Brand::get('version'))->toBe(Brand::version());
});
