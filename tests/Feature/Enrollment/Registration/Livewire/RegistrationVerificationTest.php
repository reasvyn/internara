<?php

declare(strict_types=1);

use App\Enrollment\Registration\Livewire\RegistrationVerification;
use App\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $admin = User::factory()->create();
    $admin->assignRole('super_admin');
    test()->actingAs($admin);
});

test('renders registration verification', function () {
    Livewire::test(RegistrationVerification::class)
        ->assertSuccessful();
});
