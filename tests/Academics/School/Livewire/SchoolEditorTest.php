<?php

declare(strict_types=1);

use App\Academics\School\Livewire\SchoolEditor;
use App\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $admin = User::factory()->create();
    $admin->assignRole('super_admin');
    test()->actingAs($admin);
});

test('renders the school editor component', function () {
    Livewire::test(SchoolEditor::class)
        ->assertSuccessful();
});

test('loads school data on mount', function () {
    Livewire::test(SchoolEditor::class)
        ->assertSet('form.name', '');
});

test('shows confirm dialog when showConfirm is set', function () {
    Livewire::test(SchoolEditor::class)
        ->set('showConfirm', true)
        ->assertSet('showConfirm', true);
});
