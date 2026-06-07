<?php

declare(strict_types=1);

use App\Settings\Http\Middleware\SetLocaleMiddleware;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    Route::get('/_test_locale', function () {
        return 'ok';
    })->middleware(SetLocaleMiddleware::class);
});

test('defaults to app locale when no cookie is set', function () {
    $this->get('/_test_locale');

    expect(App::getLocale())->toBe(config('app.locale'));
});

test('uses locale from cookie when supported', function () {
    Cookie::queue(Cookie::forever('locale', 'id'));

    $this->get('/_test_locale');

    expect(App::getLocale())->toBe('id');
});

test('falls back to default locale for unsupported locale cookie', function () {
    Cookie::shouldReceive('get')->with('locale', config('app.locale'))->andReturn('fr');

    $this->get('/_test_locale');

    expect(App::getLocale())->toBe(config('app.locale'));
});
