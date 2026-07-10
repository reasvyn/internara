<?php

declare(strict_types=1);

use App\Assessment\Rubric\Livewire\RubricManager;
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
    Livewire::test(RubricManager::class)
        ->assertSuccessful();
});

test('opens create rubric modal', function () {
    Livewire::test(RubricManager::class)
        ->set('rubricModal', true)
        ->assertSet('rubricModal', true);
});

test('validates rubric name is required', function () {
    Livewire::test(RubricManager::class)
        ->set('rubricModal', true)
        ->set('rubricForm.name', '')
        ->call('saveRubric')
        ->assertHasErrors(['rubricForm.name']);
});
