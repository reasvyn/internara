<?php

declare(strict_types=1);

use App\Enrollment\AccountApplication\Livewire\ApplyPage;
use App\Program\Internship\Models\Internship;
use App\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $user = User::factory()->create();
    $user->assignRole('super_admin');
    test()->actingAs($user);

    $this->internship = Internship::factory()->create(['status' => 'published']);
});

test('renders the apply page component', function () {
    Livewire::test(ApplyPage::class)
        ->assertSuccessful();
});

test('displays internships from computed property', function () {
    Livewire::test(ApplyPage::class)
        ->assertSet('internships', fn ($internships) => $internships->count() === 1);
});

test('toggles between placement and proposed company fields', function () {
    Livewire::test(ApplyPage::class)
        ->assertSet('form.use_placement', true)
        ->set('form.use_placement', false)
        ->assertSet('form.use_placement', false);
});

test('resets placement fields when toggling use_placement', function () {
    Livewire::test(ApplyPage::class)
        ->set('form.placement_id', 'some-id')
        ->set('form.proposed_company_name', 'Test Company')
        ->set('form.proposed_company_address', 'Test Address')
        ->set('form.use_placement', false)
        ->assertSet('form.placement_id', '')
        ->assertSet('form.proposed_company_name', '')
        ->assertSet('form.proposed_company_address', '');
});

test('validates internship_id is required on submit', function () {
    Livewire::test(ApplyPage::class)
        ->set('form.internship_id', '')
        ->set('form.name', 'Student Name')
        ->set('form.email', 'student@example.com')
        ->set('form.academic_year', '2025/2026')
        ->call('submit')
        ->assertHasErrors(['form.internship_id']);
});

test('validates name is required on submit', function () {
    Livewire::test(ApplyPage::class)
        ->set('form.internship_id', $this->internship->id)
        ->set('form.name', '')
        ->set('form.email', 'student@example.com')
        ->set('form.academic_year', '2025/2026')
        ->call('submit')
        ->assertHasErrors(['form.name']);
});

test('validates email is required on submit', function () {
    Livewire::test(ApplyPage::class)
        ->set('form.internship_id', $this->internship->id)
        ->set('form.name', 'Student Name')
        ->set('form.email', '')
        ->set('form.academic_year', '2025/2026')
        ->call('submit')
        ->assertHasErrors(['form.email']);
});

test('validates academic_year is required on submit', function () {
    Livewire::test(ApplyPage::class)
        ->set('form.internship_id', $this->internship->id)
        ->set('form.name', 'Student Name')
        ->set('form.email', 'student@example.com')
        ->set('form.academic_year', '')
        ->call('submit')
        ->assertHasErrors(['form.academic_year']);
});
