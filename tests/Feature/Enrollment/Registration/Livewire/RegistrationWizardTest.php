<?php

declare(strict_types=1);

use App\Enrollment\Registration\Livewire\RegistrationWizard;
use App\Program\Internship\Models\Internship;
use App\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $user = User::factory()->create();
    $user->assignRole('student');
    test()->actingAs($user);
});

test('renders the registration wizard component', function () {
    Livewire::test(RegistrationWizard::class)
        ->assertSuccessful();
});

test('starts at step 1', function () {
    Livewire::test(RegistrationWizard::class)
        ->assertSet('step', 1);
});

test('navigates to next step', function () {
    $internship = Internship::factory()->create(['status' => 'published']);

    Livewire::test(RegistrationWizard::class)
        ->set('form.internship_id', $internship->id)
        ->call('nextStep')
        ->assertSet('step', 2);
});

test('navigates to previous step', function () {
    $internship = Internship::factory()->create(['status' => 'published']);

    Livewire::test(RegistrationWizard::class)
        ->set('form.internship_id', $internship->id)
        ->call('nextStep')
        ->call('previousStep')
        ->assertSet('step', 1);
});

test('validates internship_id on step 1', function () {
    Livewire::test(RegistrationWizard::class)
        ->call('nextStep')
        ->assertSet('step', 1);
});

test('validates form on submit', function () {
    Livewire::test(RegistrationWizard::class)
        ->call('submit')
        ->assertHasErrors(['form.internship_id', 'form.academic_year']);
});
