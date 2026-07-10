<?php

declare(strict_types=1);

use App\User\Dashboard\Livewire\UserDashboard;
use App\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $user = User::factory()->create();
    $user->assignRole('student');
    test()->actingAs($user);
});

test('renders user dashboard', function () {
    Livewire::test(UserDashboard::class)
        ->assertSuccessful();
});
