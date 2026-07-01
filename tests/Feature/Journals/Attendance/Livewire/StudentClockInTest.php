<?php

declare(strict_types=1);

use App\Journals\Attendance\Livewire\StudentClockIn;
use App\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $student = User::factory()->create();
    $student->assignRole('student');
    test()->actingAs($student);
});

test('renders the student clock in component', function () {
    Livewire::test(StudentClockIn::class)
        ->assertSuccessful();
});
