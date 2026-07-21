<?php

declare(strict_types=1);

use App\Document\Handbook\Livewire\StudentHandbookList;
use App\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $student = User::factory()->create();
    $student->assignRole('student');
    test()->actingAs($student);
});

test('renders student handbook list', function () {
    Livewire::test(StudentHandbookList::class)
        ->assertSuccessful();
});
