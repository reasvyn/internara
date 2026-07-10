<?php

declare(strict_types=1);

use App\Guidance\MonitoringVisit\Livewire\StudentVisitList;
use App\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $student = User::factory()->create();
    $student->assignRole('student');
    test()->actingAs($student);
});

test('renders student visit list', function () {
    Livewire::test(StudentVisitList::class)
        ->assertSuccessful();
});
