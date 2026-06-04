<?php

declare(strict_types=1);

use App\Domain\Core\Exceptions\DomainException;
use App\Domain\Core\Exceptions\RejectedException;

test('DomainException is abstract and extends RuntimeException', function () {
    $ref = new ReflectionClass(DomainException::class);
    expect($ref->isAbstract())->toBeTrue();
    expect($ref->getParentClass()->getName())->toBe(RuntimeException::class);
});

test('DomainException is NOT an AppException (decoupled hierarchy)', function () {
    $ref = new ReflectionClass(DomainException::class);
    $parent = $ref->getParentClass()->getName();
    expect($parent)->not->toBe('App\Domain\Core\Exceptions\AppException');
});

test('RejectedException extends DomainException', function () {
    $e = new RejectedException('rejected');
    expect($e)->toBeInstanceOf(DomainException::class);
    expect($e->getMessage())->toBe('rejected');
});

test('RejectedException uses HasExceptionContext', function () {
    $e = new RejectedException('rejected')
        ->withHint('fix the data')
        ->withContext(['field' => 'email']);

    expect($e->getHint())->toBe('fix the data');
    expect($e->getContext())->toBe(['field' => 'email']);
    expect($e->toCliOutput())->toContain('rejected');
    expect($e->toCliOutput())->toContain('Hint: fix the data');
});
