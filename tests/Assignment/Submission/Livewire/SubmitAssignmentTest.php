<?php

declare(strict_types=1);

use App\Assignment\Submission\Livewire\SubmitAssignment;
use App\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $student = User::factory()->create();
    $student->assignRole('student');
    test()->actingAs($student);
});

test('renders submit assignment page', function () {
    Livewire::test(SubmitAssignment::class)
        ->assertSuccessful();
});
