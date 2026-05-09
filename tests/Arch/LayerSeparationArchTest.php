<?php

declare(strict_types=1);

arch('controllers are suffixed with Controller')
    ->expect('App\Http\Controllers')
    ->toBeClasses()
    ->toHaveSuffix('Controller');

arch('controllers do not import Actions or Models')
    ->expect('App\Http\Controllers')
    ->not->toUse('App\Actions')
    ->not->toUse('App\Models');

arch('Notifications do not import Livewire')
    ->expect('App\Notifications')
    ->not->toUse('App\Livewire');

arch('Events do not import Actions')
    ->expect('App\Events')
    ->not->toUse('App\Actions');

arch('Services do not import Livewire')
    ->expect('App\Services')
    ->not->toUse('App\Livewire');
