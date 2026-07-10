<?php

declare(strict_types=1);

use App\Settings\Locale\Support\Locale;

test('has supported locales', function () {
    $locales = Locale::all();

    expect($locales)->toHaveKeys(['en', 'id']);
});

test('english is default locale', function () {
    expect(Locale::DEFAULT_LOCALE)->toBe('en');
});

test('detects supported locale', function () {
    expect(Locale::isSupported('en'))->toBeTrue();
    expect(Locale::isSupported('id'))->toBeTrue();
    expect(Locale::isSupported('fr'))->toBeFalse();
});

test('returns locale keys', function () {
    $keys = Locale::keys();

    expect($keys)->toBe(['en', 'id']);
});

test('returns metadata for supported locale', function () {
    $meta = Locale::metadata('en');

    expect($meta)->toHaveKeys(['name', 'native']);
    expect($meta['name'])->toBe('English');
});

test('returns null metadata for unsupported locale', function () {
    expect(Locale::metadata('fr'))->toBeNull();
});

test('fallbacks to config locale when no cookie set', function () {
    $locale = Locale::current();

    expect($locale)->toBeString();
    expect(strlen($locale))->toBe(2);
});
