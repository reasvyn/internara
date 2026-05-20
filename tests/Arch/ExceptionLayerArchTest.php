<?php

declare(strict_types=1);

use App\Domain\Core\Exceptions\ActionException;
use App\Domain\Core\Exceptions\AppException;
use App\Domain\Core\Exceptions\ConflictException;
use App\Domain\Core\Exceptions\DomainException;
use App\Domain\Core\Exceptions\InfrastructureException;
use App\Domain\Core\Exceptions\NotFoundException;
use App\Domain\Core\Exceptions\PresentationException;
use App\Domain\Core\Exceptions\RateLimitException;
use App\Domain\Core\Exceptions\RejectedException;
use App\Domain\Core\Exceptions\UnauthorizedException;
use App\Domain\Core\Exceptions\ValidationFailedException;

arch('AppException is abstract')
    ->expect(AppException::class)
    ->toBeAbstract();

arch('ActionException extends AppException and is abstract')
    ->expect(ActionException::class)
    ->toExtend(AppException::class)
    ->toBeAbstract();

arch('PresentationException extends AppException and is abstract')
    ->expect(PresentationException::class)
    ->toExtend(AppException::class)
    ->toBeAbstract();

arch('InfrastructureException extends AppException and is abstract')
    ->expect(InfrastructureException::class)
    ->toExtend(AppException::class)
    ->toBeAbstract();

arch('DomainException is NOT an AppException (parallel hierarchy)')
    ->expect(DomainException::class)
    ->toExtend(RuntimeException::class)
    ->not->toExtend(AppException::class)
    ->toBeAbstract();

arch('NotFoundException extends PresentationException')
    ->expect(NotFoundException::class)
    ->toExtend(PresentationException::class);

arch('UnauthorizedException extends PresentationException')
    ->expect(UnauthorizedException::class)
    ->toExtend(PresentationException::class);

arch('ConflictException extends ActionException')
    ->expect(ConflictException::class)
    ->toExtend(ActionException::class);

arch('ValidationFailedException extends ActionException')
    ->expect(ValidationFailedException::class)
    ->toExtend(ActionException::class);

arch('RateLimitException extends InfrastructureException')
    ->expect(RateLimitException::class)
    ->toExtend(InfrastructureException::class);

arch('RejectedException extends DomainException')
    ->expect(RejectedException::class)
    ->toExtend(DomainException::class);
