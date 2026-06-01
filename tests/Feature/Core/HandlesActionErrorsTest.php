<?php

declare(strict_types=1);

use App\Domain\Core\Support\HandlesActionErrors;

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

        expect($handler->run(fn () => 'success', 'test'))->toBe('success');
    });

    it('rethrows RuntimeException directly', function () {
        $handler = new TestErrorHandler;

        $handler->run(function () {
            throw new RuntimeException('expected');
        }, 'test');
    })->throws(RuntimeException::class, 'expected');

    it('wraps generic Throwable in RuntimeException with context', function () {
        $handler = new TestErrorHandler;

        try {
            $handler->run(function () {
                throw new InvalidArgumentException('bad input');
            }, 'processing data');
        } catch (RuntimeException $e) {
            expect($e->getMessage())->toBe('processing data.')
                ->and($e->getPrevious())->toBeInstanceOf(InvalidArgumentException::class)
                ->and($e->getPrevious()->getMessage())->toBe('bad input');
        }
    });
});
