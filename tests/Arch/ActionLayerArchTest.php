<?php

declare(strict_types=1);

arch('actions have execute method')
    ->expect('App\Actions')
    ->toBeClasses()
    ->toHaveMethod('execute');

arch('actions are suffixed with Action')
    ->expect('App\Actions')
    ->toBeClasses()
    ->toHaveSuffix('Action');

arch('actions do not import Livewire')
    ->expect('App\Actions')
    ->not->toUse('App\Livewire');
