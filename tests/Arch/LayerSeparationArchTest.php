<?php

declare(strict_types=1);

use App\Domain\Core\Exceptions\AppException;
use App\Domain\Core\Http\Controllers\BaseController;

arch('BaseController is abstract')
    ->expect(BaseController::class)
    ->toBeAbstract();

arch('AppException does not import Livewire')
    ->expect(AppException::class)
    ->not->toUse('Livewire');
