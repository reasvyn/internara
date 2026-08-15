<?php

declare(strict_types=1);

use App\Core\Exceptions\ActionException;
use App\Core\Exceptions\AppException;
use App\Core\Exceptions\InfrastructureException;
use App\Core\Exceptions\ModuleException;
use App\Core\Exceptions\PresentationException;
use App\Core\Exceptions\RejectedException;
use App\Core\Exceptions\UnauthorizedException;
use App\Core\Exceptions\ValidationFailedException;

it('89SRA-FR-EH1/FR-EH5: AppException is the abstract application root with a statusCode contract', function () {
    expect((new ReflectionClass(AppException::class))->isAbstract())->toBeTrue();
    expect((new ReflectionClass(AppException::class))->isSubclassOf(RuntimeException::class))->toBeTrue();
});

it('89SRA-FR-EH2: ModuleException is the abstract business-rule root', function () {
    expect((new ReflectionClass(ModuleException::class))->isAbstract())->toBeTrue();
});

it('89SRA-FR-EH3: ModuleException does not extend AppException (independent trees)', function () {
    expect((new ReflectionClass(ModuleException::class))->isSubclassOf(AppException::class))->toBeFalse();
});

it('89SRA-FR-EH6: RejectedException extends ModuleException with status 400', function () {
    $exception = new RejectedException('Business rule violated');

    expect($exception)->toBeInstanceOf(ModuleException::class);
    expect($exception->statusCode())->toBe(400);
    expect($exception->getMessage())->toBe('Business rule violated');
});

it('89SRA-FR-EH7: ValidationFailedException extends ActionException with status 422', function () {
    $exception = new ValidationFailedException;

    expect($exception)->toBeInstanceOf(ActionException::class);
    expect($exception)->toBeInstanceOf(AppException::class);
    expect($exception->statusCode())->toBe(422);
    expect($exception->getHint())->not->toBeNull();
});

it('89SRA-FR-E5: UnauthorizedException extends PresentationException with status 403', function () {
    $exception = new UnauthorizedException;

    expect($exception)->toBeInstanceOf(PresentationException::class);
    expect($exception)->toBeInstanceOf(AppException::class);
    expect($exception->statusCode())->toBe(403);
    expect($exception->getHint())->not->toBeNull();
});

it('89SRA-FR-EH9: InfrastructureException defaults to status 500 and is not user-facing', function () {
    $exception = new class extends InfrastructureException {};

    expect($exception->statusCode())->toBe(500);
    expect($exception->isUserFacing())->toBeFalse();
});

it('89SRA-FR-E7: HasExceptionContext provides hint, context, CLI output and reporting', function () {
    $exception = new RejectedException('Nope')
        ->withHint('Provide a valid value.')
        ->withContext(['password' => 'secret-value', 'visible' => 'kept']);

    expect($exception->getHint())->toBe('Provide a valid value.');
    expect($exception->getContext())->toHaveKey('password');
    expect($exception->shouldReport())->toBeTrue();
    expect($exception->isUserFacing())->toBeTrue();
    expect($exception->getSanitizedContext()['password'])->toBe('***');
    expect($exception->getSanitizedContext()['visible'])->toBe('kept');
    expect($exception->toCliOutput())->toContain('Nope');
    expect($exception->toCliOutput())->toContain('Hint: Provide a valid value.');
    expect($exception->toCliOutput())->not->toContain('secret-value');
});
