<?php

declare(strict_types=1);

namespace Tests\Unit\Core\Actions;

use App\Core\Actions\BaseAction;
use App\Core\Exceptions\AppException;
use App\Core\Exceptions\ModuleException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ConcreteAppException extends AppException {}

class ConcreteModuleException extends ModuleException {}

class MockAction extends BaseAction
{
    public function executeTransaction(callable $callback, int $attempts = 3): mixed
    {
        return $this->transaction($callback, $attempts);
    }

    public function executeLog(string $action, array $payload = []): void
    {
        $this->log($action, null, $payload);
    }

    public function getModuleName(): string
    {
        return $this->moduleName();
    }

    public function executeWithErrorHandling(callable $callback, string $context): mixed
    {
        return $this->withErrorHandling($callback, $context);
    }
}

test('it can execute transaction callbacks', function () {
    $action = new MockAction;
    $result = $action->executeTransaction(fn () => 'hello');

    expect($result)->toBe('hello');
});

test('it skips outer transaction when already nested', function () {
    DB::beginTransaction();

    $action = new MockAction;
    $result = $action->executeTransaction(fn () => 'nested');

    expect($result)->toBe('nested');

    DB::rollBack();
});

test('it can resolve module name from class namespace', function () {
    $action = new MockAction;
    expect($action->getModuleName())->toBe('Unit');
});

test('it logs actions using smart logger', function () {
    Event::fake([MessageLogged::class]);

    $action = new MockAction;
    $action->executeLog('Test Action', ['key' => 'value']);

    Event::assertDispatched(MessageLogged::class, fn (MessageLogged $e) => $e->message === 'Test Action');
});

test('with error handling returns callback result on success', function () {
    $action = new MockAction;
    $result = $action->executeWithErrorHandling(fn () => 'ok', 'Testing');

    expect($result)->toBe('ok');
});

test('with error handling passes through app exception', function () {
    $action = new MockAction;

    expect(
        fn () => $action->executeWithErrorHandling(
            fn () => throw new ConcreteAppException('Domain error'),
            'Testing',
        ),
    )->toThrow(ConcreteAppException::class, 'Domain error');
});

test('with error handling passes through module exception', function () {
    $action = new MockAction;

    expect(
        fn () => $action->executeWithErrorHandling(
            fn () => throw new ConcreteModuleException('Module error'),
            'Testing',
        ),
    )->toThrow(ConcreteModuleException::class, 'Module error');
});

test('with error handling passes through validation exception', function () {
    $action = new MockAction;
    $e = ValidationException::withMessages(['field' => 'Required']);

    expect(fn () => $action->executeWithErrorHandling(fn () => throw $e, 'Testing'))->toThrow(
        ValidationException::class,
    );
});

test('with error handling passes through authorization exception', function () {
    $action = new MockAction;

    expect(
        fn () => $action->executeWithErrorHandling(
            fn () => throw new AuthorizationException('Not allowed'),
            'Testing',
        ),
    )->toThrow(AuthorizationException::class, 'Not allowed');
});

test('with error handling passes through model not found exception', function () {
    $action = new MockAction;

    expect(
        fn () => $action->executeWithErrorHandling(
            fn () => throw new ModelNotFoundException('Not found'),
            'Testing',
        ),
    )->toThrow(ModelNotFoundException::class, 'Not found');
});

test('with error handling passes through not found http exception', function () {
    $action = new MockAction;

    expect(
        fn () => $action->executeWithErrorHandling(
            fn () => throw new NotFoundHttpException('Missing'),
            'Testing',
        ),
    )->toThrow(NotFoundHttpException::class, 'Missing');
});

test('with error handling wraps generic throwable as runtime exception', function () {
    $action = new MockAction;

    expect(
        fn () => $action->executeWithErrorHandling(
            fn () => throw new \InvalidArgumentException('Bad arg'),
            'Processing data',
        ),
    )->toThrow(RuntimeException::class, 'Processing data.');
});

test('with error handling logs wrapped throwable', function () {
    Event::fake([MessageLogged::class]);
    $action = new MockAction;

    try {
        $action->executeWithErrorHandling(
            fn () => throw new \InvalidArgumentException('Bad arg'),
            'Processing data',
        );
    } catch (RuntimeException) {
        // expected
    }

    Event::assertDispatched(MessageLogged::class, fn (MessageLogged $e) => str_contains($e->message, 'Processing data'));
});

test('transaction wraps callback in DB transaction', function () {
    $action = new MockAction;
    $called = false;

    $result = $action->executeTransaction(function () use (&$called) {
        $called = true;

        return 'real_result';
    });

    expect($result)->toBe('real_result');
    expect($called)->toBeTrue();
});

test('it logs with empty payload by default', function () {
    Event::fake([MessageLogged::class]);

    $action = new MockAction;
    $action->executeLog('Test Action');

    Event::assertDispatched(MessageLogged::class, fn (MessageLogged $e) => $e->message === 'Test Action');
});
