<?php

declare(strict_types=1);

use App\SysAdmin\Observability\Livewire\Pulse\SystemCard;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;

uses(LazilyRefreshDatabase::class);

test('renders', function () {
    Livewire::test(SystemCard::class)
        ->assertSuccessful();
});
