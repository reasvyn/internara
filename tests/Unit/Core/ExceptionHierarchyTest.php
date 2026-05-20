<?php

declare(strict_types=1);

use App\Domain\Core\Exceptions\ActionException;
use App\Domain\Core\Exceptions\AppException;
use App\Domain\Core\Exceptions\ConflictException;
use App\Domain\Core\Exceptions\InfrastructureException;
use App\Domain\Core\Exceptions\NotFoundException;
use App\Domain\Core\Exceptions\PresentationException;
use App\Domain\Core\Exceptions\RateLimitException;
use App\Domain\Core\Exceptions\RejectedException;
use App\Domain\Core\Exceptions\UnauthorizedException;
use App\Domain\Core\Exceptions\ValidationFailedException;

describe('Exception hierarchy', function () {
    it('app exception is user facing by default', function () {
        $e = new class('test') extends AppException {};

        expect($e->isUserFacing())->toBeTrue();
    });

    it('action exception is user facing', function () {
        $e = new class('test') extends ActionException {};

        expect($e->isUserFacing())->toBeTrue();
    });

    it('infrastructure exception is not user facing', function () {
        $e = new class('test') extends InfrastructureException {};

        expect($e->isUserFacing())->toBeFalse();
    });

    it('presentation exception is user facing', function () {
        $e = new class('test') extends PresentationException {};

        expect($e->isUserFacing())->toBeTrue();
    });

    it('not-found exception has default hint', function () {
        $e = new NotFoundException;

        expect($e->getHint())->toContain('does not exist');
    });

    it('unauthorized exception has default hint', function () {
        $e = new UnauthorizedException;

        expect($e->getHint())->toContain('permission');
    });

    it('conflict exception has default hint', function () {
        $e = new ConflictException;

        expect($e->getHint())->toContain('conflict');
    });

    it('validation failed exception has default hint', function () {
        $e = new ValidationFailedException;

        expect($e->getHint())->toContain('input');
    });

    it('rate limit exception has default hint', function () {
        $e = new RateLimitException;

        expect($e->getHint())->toContain('wait');
    });

    it('domain exception reuses HasExceptionContext without extending AppException', function () {
        $e = new RejectedException('rejected');

        expect($e)->toBeInstanceOf(RuntimeException::class)
            ->not->toBeInstanceOf(AppException::class)
            ->and($e->getMessage())->toBe('rejected');
    });

    it('provides fluent hint and context API', function () {
        $e = (new ConflictException('custom'))
            ->withHint('Try again later')
            ->withContext(['key' => 'value']);

        expect($e->getHint())->toBe('Try again later')
            ->and($e->getContext())->toHaveKey('key', 'value');
    });

    it('outputs CLI formatted string', function () {
        $e = (new ValidationFailedException('Invalid input'))
            ->withHint('Check your data')
            ->withContext(['field' => 'email']);

        $output = $e->toCliOutput();

        expect($output)->toContain('Invalid input')
            ->toContain('Check your data')
            ->toContain('email');
    });
});
