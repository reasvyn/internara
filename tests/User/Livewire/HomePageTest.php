<?php

declare(strict_types=1);

use App\User\Livewire\HomePage;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;

uses(LazilyRefreshDatabase::class);

test('renders home page', function () {
    Livewire::test(HomePage::class)
        ->assertSuccessful();
});
