<?php

declare(strict_types=1);

use App\Journals\Attendance\Livewire\AttendanceManager;
use App\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $teacher = User::factory()->create();
    $teacher->assignRole('teacher');
    test()->actingAs($teacher);
});

test('renders the attendance manager component', function () {
    Livewire::test(AttendanceManager::class)
        ->assertSuccessful();
});

test('defaults to today date', function () {
    Livewire::test(AttendanceManager::class)
        ->assertSet('date', now()->toDateString());
});

test('defaults to attendance tab', function () {
    Livewire::test(AttendanceManager::class)
        ->assertSet('tab', 'attendance');
});

test('validates date is required', function () {
    Livewire::test(AttendanceManager::class)
        ->set('date', '')
        ->set('records', [])
        ->call('markAttendance')
        ->assertHasErrors(['date', 'records']);
});

test('validates records array is required', function () {
    Livewire::test(AttendanceManager::class)
        ->set('date', now()->toDateString())
        ->set('records', [])
        ->call('markAttendance')
        ->assertHasErrors(['records']);
});

test('switches to pending absences tab', function () {
    Livewire::test(AttendanceManager::class)
        ->set('tab', 'absences')
        ->assertSet('tab', 'absences');
});

test('validates record status values', function () {
    Livewire::test(AttendanceManager::class)
        ->set('date', now()->toDateString())
        ->set('records', ['invalid-id' => ['status' => 'invalid', 'notes' => '']])
        ->call('markAttendance')
        ->assertHasErrors(['records.*.status']);
});
