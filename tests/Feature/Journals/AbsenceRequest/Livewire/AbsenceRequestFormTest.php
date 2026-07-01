<?php

declare(strict_types=1);

use App\Journals\AbsenceRequest\Livewire\AbsenceRequestForm;
use App\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $student = User::factory()->create();
    $student->assignRole('super_admin');
    test()->actingAs($student);
});

test('renders the absence request form component', function () {
    Livewire::test(AbsenceRequestForm::class)
        ->assertSuccessful();
});

test('validates all fields are required', function () {
    Livewire::test(AbsenceRequestForm::class)
        ->set('startDate', '')
        ->set('reasonType', '')
        ->set('reasonDescription', '')
        ->call('submit')
        ->assertHasErrors(['startDate', 'reasonType', 'reasonDescription']);
});

test('validates reason_type must be a valid option', function () {
    Livewire::test(AbsenceRequestForm::class)
        ->set('startDate', now()->addDay()->toDateString())
        ->set('reasonType', 'invalid_type')
        ->set('reasonDescription', 'Test reason for absence')
        ->call('submit')
        ->assertHasErrors(['reasonType']);
});

test('validates start_date must be today or later', function () {
    Livewire::test(AbsenceRequestForm::class)
        ->set('startDate', now()->subDay()->toDateString())
        ->set('reasonType', 'sick')
        ->set('reasonDescription', 'Test reason for absence')
        ->call('submit')
        ->assertHasErrors(['startDate']);
});
