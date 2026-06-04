<?php

declare(strict_types=1);

use App\Domain\Core\Exceptions\ActionException;
use App\Domain\Core\Exceptions\AppException;
use App\Domain\Core\Exceptions\ConflictException;
use App\Domain\Core\Exceptions\InfrastructureException;
use App\Domain\Core\Exceptions\NotFoundException;
use App\Domain\Core\Exceptions\PresentationException;
use App\Domain\Core\Exceptions\RateLimitException;
use App\Domain\Core\Exceptions\UnauthorizedException;
use App\Domain\Core\Exceptions\ValidationFailedException;

test('AppException is abstract', function () {
    $ref = new ReflectionClass(AppException::class);
    expect($ref->isAbstract())->toBeTrue();
});

test('AppException is user-facing by default', function () {
    $e = new class('test') extends AppException {};
    expect($e->isUserFacing())->toBeTrue();
});

test('AppException should report by default', function () {
    $e = new class('test') extends AppException {};
    expect($e->shouldReport())->toBeTrue();
});

test('AppException hierarchy — ActionException is abstract', function () {
    $ref = new ReflectionClass(ActionException::class);
    expect($ref->isAbstract())->toBeTrue();
    expect((new ReflectionClass(ActionException::class))->getParentClass()->getName())
        ->toBe(AppException::class);
});

test('AppException hierarchy — InfrastructureException is abstract', function () {
    $ref = new ReflectionClass(InfrastructureException::class);
    expect($ref->isAbstract())->toBeTrue();
    expect((new ReflectionClass(InfrastructureException::class))->getParentClass()->getName())
        ->toBe(AppException::class);
});

test('AppException hierarchy — PresentationException is abstract', function () {
    $ref = new ReflectionClass(PresentationException::class);
    expect($ref->isAbstract())->toBeTrue();
    expect((new ReflectionClass(PresentationException::class))->getParentClass()->getName())
        ->toBe(AppException::class);
});

test('ConflictException extends ActionException', function () {
    $e = new ConflictException('conflict');
    expect($e)->toBeInstanceOf(ActionException::class);
    expect($e->getMessage())->toBe('conflict');
});

test('ValidationFailedException extends ActionException', function () {
    $e = new ValidationFailedException('validation failed');
    expect($e)->toBeInstanceOf(ActionException::class);
    expect($e->getMessage())->toBe('validation failed');
});

test('RateLimitException extends InfrastructureException', function () {
    $e = new RateLimitException('too many requests');
    expect($e)->toBeInstanceOf(InfrastructureException::class);
    expect($e->getMessage())->toBe('too many requests');
});

test('NotFoundException extends PresentationException', function () {
    $e = new NotFoundException('not found');
    expect($e)->toBeInstanceOf(PresentationException::class);
    expect($e->getMessage())->toBe('not found');
});

test('UnauthorizedException extends PresentationException', function () {
    $e = new UnauthorizedException('unauthorized');
    expect($e)->toBeInstanceOf(PresentationException::class);
    expect($e->getMessage())->toBe('unauthorized');
});

test('Concrete exceptions use HasExceptionContext trait', function () {
    $e = new ConflictException('test')
        ->withHint('resolve conflict')
        ->withContext(['id' => 1]);

    expect($e->getHint())->toBe('resolve conflict');
    expect($e->getContext())->toBe(['id' => 1]);
});
