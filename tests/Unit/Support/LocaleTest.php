<?php

declare(strict_types=1);

use App\Support\Locale;

beforeEach(function () {
    Session()->flush();
});

it('has default locale as Indonesian', function () {
    expect(Locale::DEFAULT_LOCALE)->toBe('id');
});

it('has supported locales', function () {
    $locales = Locale::all();

    expect($locales)->toBeArray();
    expect($locales)->toHaveKeys(['en', 'id']);
});

it('can set locale', function () {
    $result = Locale::set('en');

    expect($result)->toBeTrue();
    expect(Locale::current())->toBe('en');
});

it('returns false for unsupported locale', function () {
    $result = Locale::set('unsupported');

    expect($result)->toBeFalse();
});

it('returns default locale when not set', function () {
    expect(Locale::current())->toBe('id');
});

it('can get locale keys', function () {
    $keys = Locale::keys();

    expect($keys)->toBe(['en', 'id']);
});

it('validates supported locale', function () {
    expect(Locale::isSupported('en'))->toBeTrue();
    expect(Locale::isSupported('id'))->toBeTrue();
    expect(Locale::isSupported('fr'))->toBeFalse();
});

it('can get locale metadata', function () {
    $metadata = Locale::metadata('en');

    expect($metadata)->toBeArray();
    expect($metadata)->toHaveKey('name');
    expect($metadata['name'])->toBe('English');
});

it('returns null for non-existent locale metadata', function () {
    $metadata = Locale::metadata('xyz');

    expect($metadata)->toBeNull();
});

it('has correct locale details', function () {
    $en = Locale::metadata('en');
    $id = Locale::metadata('id');

    expect($en['native'])->toBe('English');
    expect($en['icon'])->toBe('us');
    expect($id['native'])->toBe('Bahasa Indonesia');
    expect($id['icon'])->toBe('id');
});
