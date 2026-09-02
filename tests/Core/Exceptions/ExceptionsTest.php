<?php

declare(strict_types=1);

use App\Modules\Core\Exceptions\ActionException;
use App\Modules\Core\Exceptions\AppException;
use App\Modules\Core\Exceptions\InfrastructureException;
use App\Modules\Core\Exceptions\ModuleException;
use App\Modules\Core\Exceptions\PresentationException;
use App\Modules\Core\Exceptions\RejectedException;
use App\Modules\Core\Exceptions\UnauthorizedException;
use App\Modules\Core\Exceptions\ValidationFailedException;

test('89SRA-FR-EH1/FR-EH5: AppException is the abstract application root with a statusCode contract', function () {
    expect((new ReflectionClass(AppException::class))->isAbstract())->toBeTrue();
    expect((new ReflectionClass(AppException::class))->isSubclassOf(RuntimeException::class))->toBeTrue();
});

test('89SRA-FR-EH2: ModuleException is the abstract business-rule root', function () {
    expect((new ReflectionClass(ModuleException::class))->isAbstract())->toBeTrue();
});

test('89SRA-FR-EH3: ModuleException does not extend AppException (independent trees)', function () {
    expect((new ReflectionClass(ModuleException::class))->isSubclassOf(AppException::class))->toBeFalse();
});

test('89SRA-FR-EH6: RejectedException extends ModuleException with status 400', function () {
    $exception = new RejectedException('Business rule violated');

    expect($exception)->toBeInstanceOf(ModuleException::class);
    expect($exception->statusCode())->toBe(400);
    expect($exception->getMessage())->toBe('Business rule violated');
});

test('89SRA-FR-EH7: ValidationFailedException extends ActionException with status 422', function () {
    $exception = new ValidationFailedException;

    expect($exception)->toBeInstanceOf(ActionException::class);
    expect($exception)->toBeInstanceOf(AppException::class);
    expect($exception->statusCode())->toBe(422);
    expect($exception->getHint())->not->toBeNull();
});

test('89SRA-FR-EH8: UnauthorizedException extends PresentationException with status 403', function () {
    $exception = new UnauthorizedException;

    expect($exception)->toBeInstanceOf(PresentationException::class);
    expect($exception)->toBeInstanceOf(AppException::class);
    expect($exception->statusCode())->toBe(403);
    expect($exception->getHint())->not->toBeNull();
});

test('89SRA-FR-EH9: InfrastructureException defaults to status 500 and is not user-facing', function () {
    $exception = new class extends InfrastructureException {};

    expect($exception->statusCode())->toBe(500);
    expect($exception->isUserFacing())->toBeFalse();
});

test('89SRA-FR-EH4: HasExceptionContext provides hint, context, CLI output and reporting', function () {
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

test('SE5Q9-FR-E6: InfrastructureException extends AppException with HTTP 500 and is not user-facing', function () {
    $exception = new class extends InfrastructureException {};

    expect($exception)->toBeInstanceOf(AppException::class);
    expect($exception->statusCode())->toBe(500);
    expect($exception->isUserFacing())->toBeFalse();
});

test('SE5Q9-FR-E7: HasExceptionContext trait provides hint, context, CLI output, isUserFacing and shouldReport', function () {
    $exception = new RejectedException('Test error')
        ->withHint('Try again later.')
        ->withContext(['user_id' => 123, 'token' => 'secret']);

    expect($exception->getHint())->toBe('Try again later.');
    expect($exception->getContext())->toHaveKey('user_id');
    expect($exception->getContext()['user_id'])->toBe(123);
    expect($exception->isUserFacing())->toBeTrue();
    expect($exception->shouldReport())->toBeTrue();
    expect($exception->toCliOutput())->toContain('Test error');
    expect($exception->toCliOutput())->toContain('Hint: Try again later.');
    expect($exception->toCliOutput())->toContain('user_id: 123');
    expect($exception->getSanitizedContext()['token'])->toBe('***');
});

test('SE5Q9-NFR-M5: Module discovery at runtime - no manual registration required', function () {
    $livewireNamespace = 'App\\Modules\\Core\\Livewire';
    $policyNamespace = 'App\\Modules\\Core\\Policies';

    expect(class_exists($livewireNamespace.'\\BaseRecordManager'))->toBeTrue();
    expect(class_exists($policyNamespace.'\\BasePolicy'))->toBeTrue();
    expect(config('module.list'))->toBeArray();
    expect(config('module.list'))->not->toBeEmpty();
});
