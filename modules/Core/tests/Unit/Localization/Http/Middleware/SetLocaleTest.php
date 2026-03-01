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
    test('it fulfills [SYRS-NF-403] by setting application locale from session', function () {
        Session::put('locale', 'id');

        $request = Request::create('/', 'GET');
        $middleware = new SetLocale;

        $middleware->handle($request, function ($req) {
            expect(App::getLocale())->toBe('id');

            return response('OK');
        });
    });

    test('it maintains default locale if session is empty', function () {
        $initialLocale = App::getLocale();
        Session::forget('locale');

        $request = Request::create('/', 'GET');
        $middleware = new SetLocale;

        $middleware->handle($request, function ($req) use ($initialLocale) {
            expect(App::getLocale())->toBe($initialLocale);

            return response('OK');
        });
    });
});
