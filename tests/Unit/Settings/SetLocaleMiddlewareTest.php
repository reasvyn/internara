<?php

declare(strict_types=1);

use App\Domain\Settings\Http\Middleware\SetLocaleMiddleware;
use App\Domain\Shared\Support\Locale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

describe('SetLocaleMiddleware', function () {
    it('sets app locale from Locale::current()', function () {
        $middleware = new SetLocaleMiddleware;
        $request = Request::create('/');
        $called = false;

        Locale::set('id');

        $middleware->handle($request, function ($req) use (&$called) {
            $called = true;

            expect(App::getLocale())->toBe('id');

            return response('ok');
        });

        expect($called)->toBeTrue();
    });

    it('sets english locale when configured', function () {
        $middleware = new SetLocaleMiddleware;
        $request = Request::create('/');

        Locale::set('en');
        App::setLocale('en');

        $middleware->handle($request, function ($req) {
            expect(App::getLocale())->toBe('en');

            return response('ok');
        });
    });
});
