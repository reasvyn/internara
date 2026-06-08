<?php

declare(strict_types=1);

use App\Settings\Locale\Support\Locale;
use Illuminate\Support\Facades\Cookie;

test('supported locales are configured', function () {
    expect(Locale::all())->toHaveKeys(['en', 'id']);
});

test('keys returns locale codes', function () {
    expect(Locale::keys())->toBe(['en', 'id']);
});

test('isSupported checks locale validity', function () {
    expect(Locale::isSupported('en'))->toBeTrue();
    expect(Locale::isSupported('id'))->toBeTrue();
    expect(Locale::isSupported('fr'))->toBeFalse();
});

test('default locale is en', function () {
    expect(Locale::DEFAULT_LOCALE)->toBe('en');
});

test('current returns cookie value when supported', function () {
    Cookie::shouldReceive('get')->with('locale', config('app.locale'))->andReturn('id');

    expect(Locale::current())->toBe('id');
});

test('current falls back to app locale when cookie is unsupported', function () {
    Cookie::shouldReceive('get')->with('locale', config('app.locale'))->andReturn('fr');

    expect(Locale::current())->toBe(config('app.locale'));
});

test('set returns true and queues cookie for supported locale', function () {
    Cookie::shouldReceive('queue')->once();
    Cookie::shouldReceive('forever')->with('locale', 'id')->andReturnSelf();

    $result = Locale::set('id');

    expect($result)->toBeTrue();
});

test('set returns false for unsupported locale', function () {
    $result = Locale::set('fr');

    expect($result)->toBeFalse();
});

test('metadata returns locale data', function () {
    expect(Locale::metadata('en'))->toBe(['name' => 'English', 'native' => 'English']);
    expect(Locale::metadata('fr'))->toBeNull();
});
