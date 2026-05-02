<?php

declare(strict_types=1);

namespace Modules\Core\Tests\Unit\Localization\Http\Middleware;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;
use Modules\Core\Localization\Http\Middleware\SetLocale;

uses(RefreshDatabase::class);

describe('SetLocale Middleware', function () {
    beforeEach(function () {
        Session::forget('locale');
    });

    test('it sets locale from session when locale is supported', function () {
        Session::put('locale', 'id');

        $request = Request::create('/', 'GET');
        $middleware = new SetLocale;

        $middleware->handle($request, function ($req) {
            expect(App::getLocale())->toBe('id');

            return response('OK');
        });
    });

    test('it sets locale from session for English', function () {
        Session::put('locale', 'en');

        $request = Request::create('/', 'GET');
        $middleware = new SetLocale;

        $middleware->handle($request, function ($req) {
            expect(App::getLocale())->toBe('en');

            return response('OK');
        });
    });

    test('it does not change locale when session locale is not supported', function () {
        $initialLocale = App::getLocale();
        Session::put('locale', 'invalid_locale');

        $request = Request::create('/', 'GET');
        $middleware = new SetLocale;

        $middleware->handle($request, function ($req) use ($initialLocale) {
            expect(App::getLocale())->toBe($initialLocale);

            return response('OK');
        });
    });

    test('it does not change locale when session is empty', function () {
        $initialLocale = App::getLocale();
        Session::forget('locale');

        $request = Request::create('/', 'GET');
        $middleware = new SetLocale;

        $middleware->handle($request, function ($req) use ($initialLocale) {
            expect(App::getLocale())->toBe($initialLocale);

            return response('OK');
        });
    });

    test('it passes request to next handler after setting locale', function () {
        Session::put('locale', 'id');
        $request = Request::create('/', 'GET');
        $middleware = new SetLocale;

        $nextCalled = false;
        $result = $middleware->handle($request, function ($req) use (&$nextCalled) {
            $nextCalled = true;

            return response('Handled');
        });

        expect($nextCalled)->toBeTrue();
        expect($result->getContent())->toBe('Handled');
    });
});
