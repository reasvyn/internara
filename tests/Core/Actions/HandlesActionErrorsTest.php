<?php

declare(strict_types=1);

use App\Core\Actions\Concerns\HandlesActionErrors;
use App\Core\Exceptions\RejectedException;
use App\User\Models\User;
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

it('89SRA-FR-EH*: returns the callback result on success', function () {
    expect((new HandlesActionErrorsTestStub)->run(fn () => 'done'))->toBe('done');
});

it('89SRA-FR-EH*: rethrows known exception types unchanged', function (Throwable $exception) {
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

it('89SRA-FR-EH*: wraps unknown exceptions in a RuntimeException with logging', function () {
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
