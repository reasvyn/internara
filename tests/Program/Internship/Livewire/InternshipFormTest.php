<?php

declare(strict_types=1);

use App\Program\Internship\Livewire\InternshipManager;
use App\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $admin = User::factory()->create();
    $admin->assignRole('super_admin');
    test()->actingAs($admin);
});

test('renders within internship manager', function () {
    Livewire::test(InternshipManager::class)
        ->assertSuccessful();
});

test('validates name is required via form', function () {
    Livewire::test(InternshipManager::class)
        ->call('create')
        ->set('form.name', '')
        ->set('form.start_date', '')
        ->set('form.end_date', '')
        ->call('save')
        ->assertHasErrors(['form.name']);
});

test('validates start_date is required via form', function () {
    Livewire::test(InternshipManager::class)
        ->call('create')
        ->set('form.name', 'Test Internship')
        ->set('form.start_date', '')
        ->set('form.end_date', '')
        ->call('save')
        ->assertHasErrors(['form.start_date']);
});

test('validates end_date is required via form', function () {
    Livewire::test(InternshipManager::class)
        ->call('create')
        ->set('form.name', 'Test Internship')
        ->set('form.start_date', '2026-01-01')
        ->set('form.end_date', '')
        ->call('save')
        ->assertHasErrors(['form.end_date']);
});
