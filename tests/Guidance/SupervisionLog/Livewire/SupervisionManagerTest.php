<?php

declare(strict_types=1);

use App\Guidance\SupervisionLog\Livewire\SupervisionManager;
use App\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $student = User::factory()->create();
    $student->assignRole('student');
    test()->actingAs($student);
});

test('renders the supervision manager component for students', function () {
    Livewire::test(SupervisionManager::class)
        ->assertSuccessful();
});

test('handles student with no active registration', function () {
    Livewire::test(SupervisionManager::class)
        ->assertSet('registration', null);
});
