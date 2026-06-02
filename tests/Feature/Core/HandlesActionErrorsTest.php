<?php

declare(strict_types=1);

namespace Tests\Feature\Core;

use App\Domain\Core\Exceptions\ConflictException;
use App\Domain\Core\Exceptions\DomainException;
use App\Domain\Core\Exceptions\RejectedException;
use App\Domain\Core\Support\HandlesActionErrors;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

uses(LazilyRefreshDatabase::class);

class TestErrorHandler
{
    use HandlesActionErrors;

    public function run(callable $callback, string $context): mixed
    {
        return $this->withErrorHandling($callback, $context);
    }
}

describe('HandlesActionErrors', function () {
    it('returns callback result on success', function () {
        $handler = new TestErrorHandler;

        $result = $handler->run(fn () => 'success', 'test');

        expect($result)->toBe('success');
    });

    it('rethrows RuntimeException directly', function () {
        $handler = new TestErrorHandler;

        $handler->run(fn () => throw new \RuntimeException('known error'), 'test');
    })->throws(\RuntimeException::class, 'known error');

    it('rethrows AppException directly', function () {
        $handler = new TestErrorHandler;

        $handler->run(fn () => throw new ConflictException('conflict'), 'test');
    })->throws(ConflictException::class, 'conflict');

    it('rethrows DomainException directly', function () {
        $handler = new TestErrorHandler;

        $handler->run(fn () => throw new RejectedException('rejected'), 'test');
    })->throws(DomainException::class, 'rejected');

    it('rethrows ValidationException directly', function () {
        $handler = new TestErrorHandler;

        $handler->run(fn () => throw ValidationException::withMessages(['field' => 'invalid']), 'test');
    })->throws(ValidationException::class);

    it('rethrows AuthorizationException directly', function () {
        $handler = new TestErrorHandler;

        $handler->run(fn () => throw new AuthorizationException, 'test');
    })->throws(AuthorizationException::class);

    it('rethrows ModelNotFoundException directly', function () {
        $handler = new TestErrorHandler;

        $handler->run(fn () => throw new ModelNotFoundException, 'test');
    })->throws(ModelNotFoundException::class);

    it('rethrows NotFoundHttpException directly', function () {
        $handler = new TestErrorHandler;

        $handler->run(fn () => throw new NotFoundHttpException, 'test');
    })->throws(NotFoundHttpException::class);

    it('wraps unknown Throwable as RuntimeException with context', function () {
        $handler = new TestErrorHandler;

        $handler->run(fn () => throw new \InvalidArgumentException('bad arg'), 'Processing data');
    })->throws(\RuntimeException::class, 'Processing data.');
});
