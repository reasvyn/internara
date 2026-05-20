<?php

declare(strict_types=1);

use App\Domain\Core\Actions\BaseAction;

arch('BaseAction is abstract')
    ->expect(BaseAction::class)
    ->toBeAbstract()
    ->toHaveMethod('transaction')
    ->toHaveMethod('log');

arch('BaseAction does not import Livewire')
    ->expect(BaseAction::class)
    ->not->toUse('Livewire');
