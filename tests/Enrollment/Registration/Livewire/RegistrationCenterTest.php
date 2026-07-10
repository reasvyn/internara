<?php

declare(strict_types=1);

use App\Enrollment\Registration\Livewire\RegistrationCenter;
use App\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $user = User::factory()->create();
    $user->assignRole('student');
    test()->actingAs($user);
});

test('renders the registration center component', function () {
    Livewire::test(RegistrationCenter::class)
        ->assertSuccessful();
});
