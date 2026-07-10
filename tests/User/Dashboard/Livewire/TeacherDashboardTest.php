<?php

declare(strict_types=1);

use App\User\Dashboard\Livewire\TeacherDashboard;
use App\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $teacher = User::factory()->create();
    $teacher->assignRole('teacher');
    test()->actingAs($teacher);
});

test('renders teacher dashboard', function () {
    Livewire::test(TeacherDashboard::class)
        ->assertSuccessful();
});
