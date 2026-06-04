<?php

declare(strict_types=1);

use App\Domain\Core\Exceptions\ConflictException;
use App\Domain\Core\Exceptions\RejectedException;
use App\Domain\Core\Support\HandlesActionErrors;

test('HandlesActionErrors wraps successful execution', function () {
    $class = new class
    {
        use HandlesActionErrors;

        public function run(): mixed
        {
            return $this->withErrorHandling(fn () => 'ok', 'Test context');
        }
    };

    expect($class->run())->toBe('ok');
});

test('HandlesActionErrors rethrows RuntimeException directly', function () {
    $class = new class
    {
        use HandlesActionErrors;

        public function run(): mixed
        {
            return $this->withErrorHandling(function () {
                throw new RuntimeException('Direct runtime');
            }, 'Test');
        }
    };

    expect(fn () => $class->run())->toThrow(RuntimeException::class, 'Direct runtime');
});

test('HandlesActionErrors rethrows AppException directly', function () {
    $class = new class
    {
        use HandlesActionErrors;

        public function run(): mixed
        {
            return $this->withErrorHandling(function () {
                throw new ConflictException('Conflict');
            }, 'Test');
        }
    };

    expect(fn () => $class->run())->toThrow(ConflictException::class);
});

test('HandlesActionErrors rethrows DomainException directly', function () {
    $class = new class
    {
        use HandlesActionErrors;

        public function run(): mixed
        {
            return $this->withErrorHandling(function () {
                throw new RejectedException('Rejected');
            }, 'Test');
        }
    };

    expect(fn () => $class->run())->toThrow(RejectedException::class);
});

test('HandlesActionErrors wraps generic Throwable as RuntimeException', function () {
    $class = new class
    {
        use HandlesActionErrors;

        public function run(): mixed
        {
            return $this->withErrorHandling(function () {
                throw new InvalidArgumentException('Bad arg');
            }, 'Custom context');
        }
    };

    expect(fn () => $class->run())->toThrow(RuntimeException::class, 'Custom context.');
});
