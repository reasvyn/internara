<?php

declare(strict_types=1);

use App\Enrollment\Placement\Livewire\StudentPlacementChangeRequest;
use App\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $student = User::factory()->create();
    $student->assignRole('student');
    test()->actingAs($student);
});

test('renders student placement change request', function () {
    Livewire::test(StudentPlacementChangeRequest::class)
        ->assertSuccessful();
});
