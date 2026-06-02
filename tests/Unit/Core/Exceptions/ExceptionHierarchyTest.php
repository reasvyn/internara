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
    it('AppException and PresentationException are user-facing', function () {
        $ae = new class('test') extends ActionException {};
        $pe = new class('test') extends PresentationException {};

        expect($ae->isUserFacing())->toBeTrue()
            ->and($pe->isUserFacing())->toBeTrue();
    });

    it('InfrastructureException is not user-facing', function () {
        $e = new class('test') extends InfrastructureException {};

        expect($e->isUserFacing())->toBeFalse();
    });

    it('DomainException is independent from AppException tree', function () {
        $e = new RejectedException('rejected');

        expect($e)->toBeInstanceOf(RuntimeException::class)
            ->not->toBeInstanceOf(AppException::class);
    });

    it('concrete exceptions have default hints', function () {
        expect((new NotFoundException)->getHint())->toContain('does not exist');
        expect((new UnauthorizedException)->getHint())->toContain('permission');
        expect((new ConflictException)->getHint())->toContain('conflict');
        expect((new ValidationFailedException)->getHint())->toContain('input');
        expect((new RateLimitException)->getHint())->toContain('wait');
    });

    it('withHint and withContext are chainable', function () {
        $e = (new ConflictException('custom'))
            ->withHint('Try again')
            ->withContext(['key' => 'value']);

        expect($e->getHint())->toBe('Try again')
            ->and($e->getContext())->toHaveKey('key', 'value');
    });

    it('toCliOutput formats message with hint and context', function () {
        $e = (new ValidationFailedException('Invalid input'))
            ->withHint('Check your data')
            ->withContext(['field' => 'email']);

        $output = $e->toCliOutput();

        expect($output)->toContain('Invalid input')
            ->and($output)->toContain('Check your data')
            ->and($output)->toContain('email');
    });

    it('reports by default', function () {
        $e = new RejectedException('test');

        expect($e->shouldReport())->toBeTrue();
    });

    it('concrete AppException is instance of its hierarchy', function () {
        $e = new NotFoundException;

        expect($e)->toBeInstanceOf(AppException::class)
            ->and($e)->toBeInstanceOf(PresentationException::class)
            ->and($e)->toBeInstanceOf(RuntimeException::class);
    });

    it('ValidationFailedException is an ActionException', function () {
        $e = new ValidationFailedException;

        expect($e)->toBeInstanceOf(ActionException::class)
            ->and($e)->toBeInstanceOf(AppException::class);
    });

    it('RateLimitException is an InfrastructureException', function () {
        $e = new RateLimitException;

        expect($e)->toBeInstanceOf(InfrastructureException::class)
            ->and($e)->isUserFacing()->toBeFalse();
    });
});
