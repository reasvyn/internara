<?php

declare(strict_types=1);

arch('Notifications do not import Livewire')
    ->expect('App\Domain')
    ->toExtend('Illuminate\Notifications\Notification')
    ->not->toUse('Livewire\Component');

arch('Notifications have toCustomDatabase method')
    ->expect('App\Domain')
    ->classes()
    ->toExtend('Illuminate\Notifications\Notification')
    ->toHaveMethod('toCustomDatabase');
