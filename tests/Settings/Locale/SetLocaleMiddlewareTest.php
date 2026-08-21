<?php

declare(strict_types=1);

use App\Settings\Locale\Http\Middleware\SetLocaleMiddleware;
use App\Settings\Locale\Support\Locale;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

uses(LazilyRefreshDatabase::class);

function setLocaleRun(Request $request): Response
{
    $next = fn (Request $r) => new Response('ok');

    return (new SetLocaleMiddleware)->handle($request, $next);
}

beforeEach(function () {
    Session::flush();
    Locale::set('id');
});

test('2CF4Y-FR-MW7: sets locale from current Locale resolution', function () {
    Locale::set('en');

    $request = Request::create('/');
    $response = setLocaleRun($request);

    expect($response->getStatusCode())->toBe(200);
    expect(App::getLocale())->toBe('en');
});

test('2CF4Y-FR-MW7: defaults to Indonesian when no locale set', function () {
    Locale::set('id');

    $request = Request::create('/');
    $response = setLocaleRun($request);

    expect(App::getLocale())->toBe('id');
});
