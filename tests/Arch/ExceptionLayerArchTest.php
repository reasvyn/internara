<?php

declare(strict_types=1);

use App\Exceptions\ActionException;
use App\Exceptions\AppException;
use App\Exceptions\DomainException;
use App\Exceptions\InfrastructureException;
use App\Exceptions\PresentationException;

arch('AppException is the base hierarchy root')
    ->expect(AppException::class)
    ->toBeAbstract();

arch('all exception classes extend AppException')
    ->expect('App\Exceptions')
    ->toBeClasses()
    ->toExtend(AppException::class)
    ->ignoring([
        AppException::class,
        DomainException::class,
    ]);

arch('ActionException extends AppException')
    ->expect(ActionException::class)
    ->toExtend(AppException::class);

arch('PresentationException extends AppException')
    ->expect(PresentationException::class)
    ->toExtend(AppException::class);

arch('InfrastructureException extends AppException')
    ->expect(InfrastructureException::class)
    ->toExtend(AppException::class);
