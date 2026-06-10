<?php

declare(strict_types=1);

use App\Core\Exceptions\AppException;
use App\Core\Exceptions\ModuleException;
use App\Core\Support\HandlesActionErrors;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

beforeEach(function () {
    $this->trait = new class
    {
        use HandlesActionErrors;

        public function run(callable $callback, string $context = 'test'): mixed
        {
            return $this->withErrorHandling($callback, $context);
        }
    };
});

test('handles success case', function () {
    $result = $this->trait->run(fn () => 'success');

    expect($result)->toBe('success');
});

test('rethrows runtime exception', function () {
    $this->expectException(RuntimeException::class);
    $this->expectExceptionMessage('expected error');

    $this->trait->run(fn () => throw new RuntimeException('expected error'));
});

test('rethrows app exception', function () {
    $this->expectException(AppException::class);

    $this->trait->run(fn () => throw new class extends AppException
    {
        public function statusCode(): int
        {
            return 400;
        }
    });

    // Cleanup: satisfy Pest no future expectations
    expect(true)->toBeTrue();
});

test('rethrows module exception', function () {
    $this->expectException(ModuleException::class);

    $this->trait->run(fn () => throw new class extends ModuleException
    {
        public function statusCode(): int
        {
            return 400;
        }
    });

    expect(true)->toBeTrue();
});

test('rethrows validation exception', function () {
    $this->expectException(ValidationException::class);

    $this->trait->run(fn () => throw ValidationException::withMessages([]));
});

test('rethrows authorization exception', function () {
    $this->expectException(AuthorizationException::class);

    $this->trait->run(fn () => throw new AuthorizationException);
});

test('rethrows model not found exception', function () {
    $this->expectException(ModelNotFoundException::class);

    $this->trait->run(fn () => throw new ModelNotFoundException()->setModel('Test'));
});

test('rethrows not found http exception', function () {
    $this->expectException(NotFoundHttpException::class);

    $this->trait->run(fn () => throw new NotFoundHttpException);
});

test('wraps unknown exception in runtime exception', function () {
    $this->expectException(RuntimeException::class);
    $this->expectExceptionMessage('test.');

    $this->trait->run(fn () => throw new Exception('unexpected'));
});
