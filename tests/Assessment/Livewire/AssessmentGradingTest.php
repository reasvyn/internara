<?php

declare(strict_types=1);

use App\Assessment\Livewire\AssessmentGrading;
use App\Enrollment\Registration\Models\Registration;
use App\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $admin = User::factory()->create();
    $admin->assignRole('super_admin');
    test()->actingAs($admin);
});

test('renders', function () {
    $registration = Registration::factory()->create();

    Livewire::test(AssessmentGrading::class, ['registrationId' => $registration->id])
        ->assertSuccessful();
});
