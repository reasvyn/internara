<?php

declare(strict_types=1);

namespace Modules\Exception\Tests\Unit\Concerns;

use Illuminate\Http\Request;
use Modules\Exception\AppException;
use Modules\Exception\Concerns\HandlesAppException;

class TestExceptionClass
{
    use HandlesAppException;

    public function callIsAppException(\Throwable $e): bool
    {
        return $this->isAppException($e);
    }

    public function callNewAppException(string $msg): AppException
    {
        return $this->newAppException($msg);
    }

    public function callHandleAppException(\Throwable $e, Request $r)
    {
        return $this->handleAppException($e, $r);
    }
}

test('it identifies AppException', function () {
    $class = new TestExceptionClass();
    $e = new AppException('test');

    expect($class->callIsAppException($e))
        ->toBeTrue()
        ->and($class->callIsAppException(new \Exception('test')))
        ->toBeFalse();
});

test('it creates new AppException', function () {
    $class = new TestExceptionClass();
    $e = $class->callNewAppException('test::msg');

    expect($e)->toBeInstanceOf(AppException::class);
});

test('it handles AppException in JSON request', function () {
    $class = new TestExceptionClass();
    $e = new AppException('User message', code: 403);

    $request = mock(Request::class);
    $request->shouldReceive('isLivewire')->andReturn(false);
    $request->shouldReceive('expectsJson')->andReturn(true);

    $response = $class->callHandleAppException($e, $request);

    expect($response->getStatusCode())
        ->toBe(403)
        ->and($response->getData(true)['message'])
        ->toBe('User message');
});

test('it handles generic exception in JSON request', function () {
    config(['app.debug' => false]);
    $class = new TestExceptionClass();
    $e = new \Exception('Internal error');

    $request = mock(Request::class);
    $request->shouldReceive('isLivewire')->andReturn(false);
    $request->shouldReceive('expectsJson')->andReturn(true);

    app()->setLocale('en');
    $response = $class->callHandleAppException($e, $request);

    expect($response->getStatusCode())
        ->toBe(500)
        ->and($response->getData(true)['message'])
        ->toContain('unexpected error');
});
