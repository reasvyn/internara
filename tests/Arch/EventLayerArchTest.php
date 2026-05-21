<?php

declare(strict_types=1);

arch('Events do not import Actions')
    ->expect('App\Domain')
    ->toImplement('App\Domain\Core\Contracts\DomainEvent')
    ->not->toUse('App\Domain\Core\Actions\BaseAction');

arch('Listeners are suffixed with Listener')
    ->expect('App\Domain')
    ->classes()
    ->toExtend('Illuminate\Contracts\Queue\ShouldQueue')
    ->toHaveSuffix('Listener');
