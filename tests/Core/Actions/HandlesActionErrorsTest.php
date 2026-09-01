<?php

declare(strict_types=1);

use App\Modules\Core\Actions\Concerns\HandlesActionErrors;
use App\Modules\Core\Exceptions\ActionFailedException;
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
    'InfrastructureException' => fn () => new ActionFailedException('known'),
    'ValidationException' => fn () => ValidationException::withMessages(['name' => ['required']]),
    'AuthorizationException' => fn () => new AuthorizationException,
    'ModelNotFoundException' => fn () => (new ModelNotFoundException)->setModel(User::class),
    'NotFoundHttpException' => fn () => new NotFoundHttpException,
]);

test('89SRA-FR-AE3/FR-AE4: wraps unknown exceptions in an ActionFailedException with logging', function () {
    expect(fn () => (new HandlesActionErrorsTestStub)->run(fn () => throw new LogicException('boom')))
        ->toThrow(function (ActionFailedException $e) {
            expect($e->getMessage())->toBe('test action.');
            expect($e->getPrevious())->toBeInstanceOf(LogicException::class);
            expect($e->getContext()['original_error'])->toBe('boom');

            return true;
        });
});
