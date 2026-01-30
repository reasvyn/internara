<?php

declare(strict_types=1);

namespace Modules\Exception\Tests\Unit;

use Illuminate\Http\Request;
use Modules\Exception\AppException;
use Modules\Exception\RecordNotFoundException;

test('AppException returns translated user message', function () {
    app()->setLocale('en');
    $exception = new AppException('shared::exceptions.unique_violation', [
        'column' => 'email',
        'record' => 'User',
    ]);

    expect($exception->getUserMessage())
        ->toContain('email')
        ->and($exception->getUserMessage())
        ->toContain('User');
});

test('RecordNotFoundException has correct defaults', function () {
    $exception = new RecordNotFoundException(record: ['id' => '123']);

    expect($exception->getCode())
        ->toBe(404)
        ->and($exception->getContext())
        ->toBe(['record' => ['id' => '123']]);
});

test('AppException renders json response correctly', function () {
    $request = mock(Request::class);
    $request->shouldReceive('expectsJson')->andReturn(true);
    $request->shouldReceive('input')->andReturn([]);

    $exception = new AppException('Error message', code: 400);
    $response = $exception->render($request);

    expect($response->getStatusCode())
        ->toBe(400)
        ->and($response->getData(true)['message'])
        ->toBe('Error message');
});
