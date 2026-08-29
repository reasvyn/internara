<?php

declare(strict_types=1);

use App\Modules\Core\Actions\Concerns\HandlesActionErrors;
use App\Modules\Core\Exceptions\RejectedException;
use App\Modules\User\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class HandlesActionErrorsTestStub
{
    use HandlesActionErrors;

    public function run(callable $callback, string $context = 'test action'): mixed
    {
        return $this->withErrorHandling($callback, $context);
    }
}

test('89SRA-FR-AE1: returns the callback result on success', function () {
    expect((new HandlesActionErrorsTestStub)->run(fn () => 'done'))->toBe('done');
});

test('89SRA-FR-AE2: rethrows known exception types unchanged', function (Throwable $exception) {
    expect(fn () => (new HandlesActionErrorsTestStub)->run(fn () => throw $exception))
        ->toThrow(get_class($exception));
})->with([
    'AppException-family' => fn () => new RejectedException('rejected'),
    'RuntimeException' => fn () => new RuntimeException('known'),
    'ValidationException' => fn () => ValidationException::withMessages(['name' => ['required']]),
    'AuthorizationException' => fn () => new AuthorizationException,
    'ModelNotFoundException' => fn () => (new ModelNotFoundException)->setModel(User::class),
    'NotFoundHttpException' => fn () => new NotFoundHttpException,
]);

test('89SRA-FR-AE3/FR-AE4: wraps unknown exceptions in a RuntimeException with logging', function () {
    $logs = captureLogs();

    expect(fn () => (new HandlesActionErrorsTestStub)->run(fn () => throw new LogicException('boom')))
        ->toThrow(function (RuntimeException $e) {
            expect($e->getMessage())->toBe('test action.');
            expect($e->getPrevious())->toBeInstanceOf(LogicException::class);

            return true;
        });

    $error = $logs->last();
    expect($error->level)->toBe('error');
    expect($error->message)->toBe('test action');
    expect($error->context['payload']['error'])->toBe('boom');
});
