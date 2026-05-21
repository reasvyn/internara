<?php

declare(strict_types=1);

use App\Domain\Core\Support\HandlesActionErrors;

class TestHandlesActionErrors
{
    use HandlesActionErrors;

    public function run(callable $callback, string $context): mixed
    {
        return $this->withErrorHandling($callback, $context);
    }
}

describe('HandlesActionErrors', function () {
    it('returns callback result on success', function () {
        $handler = new TestHandlesActionErrors;

        $result = $handler->run(fn () => 'success', 'test operation');

        expect($result)->toBe('success');
    });

    it('rethrows RuntimeException', function () {
        $handler = new TestHandlesActionErrors;

        $handler->run(function () {
            throw new RuntimeException('expected error');
        }, 'test operation');
    })->throws(RuntimeException::class, 'expected error');

    it('wraps generic Throwable in RuntimeException', function () {
        $handler = new TestHandlesActionErrors;

        $handler->run(function () {
            throw new InvalidArgumentException('bad input');
        }, 'processing data');
    })->throws(RuntimeException::class, 'processing data.');

    it('is a trait', function () {
        $traits = class_uses(TestHandlesActionErrors::class);

        expect($traits)->toContain(HandlesActionErrors::class);
    });

    it('preserves original exception as previous', function () {
        $handler = new TestHandlesActionErrors;

        try {
            $handler->run(function () {
                throw new InvalidArgumentException('deep cause');
            }, 'validating');
        } catch (RuntimeException $e) {
            expect($e->getPrevious())->toBeInstanceOf(InvalidArgumentException::class)
                ->and($e->getPrevious()->getMessage())->toBe('deep cause');
        }
    });
});
