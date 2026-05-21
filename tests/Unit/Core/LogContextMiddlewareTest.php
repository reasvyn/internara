<?php

declare(strict_types=1);

use App\Domain\Core\Http\Middleware\LogContext;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

describe('LogContext middleware', function () {
    it('injects request_id into log context', function () {
        Log::spy();

        $middleware = new LogContext;
        $request = Request::create('/test');
        $response = new Response('ok');

        $middleware->handle($request, fn ($req) => $response);

        Log::shouldHaveReceived('withContext')
            ->with(Mockery::on(fn ($ctx) => isset($ctx['request_id']) && $ctx['method'] === 'GET'));
    });

    it('passes request to next handler', function () {
        $middleware = new LogContext;
        $request = Request::create('/test');

        $handled = false;
        $middleware->handle($request, function ($req) use (&$handled) {
            $handled = true;

            return new Response('ok');
        });

        expect($handled)->toBeTrue();
    });

    it('returns the response from next handler', function () {
        $middleware = new LogContext;
        $request = Request::create('/test');

        $result = $middleware->handle($request, fn ($req) => new Response('hello', 201));

        expect($result->getStatusCode())->toBe(201)
            ->and($result->getContent())->toBe('hello');
    });
});
