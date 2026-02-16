<?php

declare(strict_types=1);

use Livewire\Livewire;
use Modules\Attendance\Livewire\AttendanceManager;
use Modules\Attendance\Models\AttendanceLog;
use Modules\Internship\Models\InternshipRegistration;
use Modules\Permission\Models\Role;
use Modules\User\Models\User;

beforeEach(function () {
    Role::firstOrCreate(['name' => 'student', 'guard_name' => 'web']);
});

test('student can clock in', function () {
    $student = User::factory()->create();
    $student->assignRole('student');

    // Create an active registration for the student
    $registration = InternshipRegistration::factory()->create([
        'student_id' => $student->id,
    ]);
    $registration->setStatus('active');

    $this->actingAs($student);

    Livewire::test(AttendanceManager::class)->call('clockIn')->assertOk();

    $log = AttendanceLog::where('student_id', $student->id)->first();
    expect($log)->not->toBeNull();
    expect($log->date->format('Y-m-d'))->toBe(now()->format('Y-m-d'));
    expect($log->check_in_at)->not->toBeNull();
});

test('student can clock out', function () {
    $student = User::factory()->create();
    $student->assignRole('student');

    $registration = InternshipRegistration::factory()->create([
        'student_id' => $student->id,
    ]);
    $registration->setStatus('active');

    // Create existing clock-in log
    AttendanceLog::create([
        'registration_id' => $registration->id,
        'student_id' => $student->id,
        'date' => now()->format('Y-m-d'),
        'check_in_at' => now()->subHours(4),
    ]);

    $this->actingAs($student);

    Livewire::test(AttendanceManager::class)->call('clockOut')->assertOk();

    $log = AttendanceLog::where('student_id', $student->id)->first();
    expect($log->check_out_at)->not->toBeNull();
});

test('student cannot clock in twice in the same day', function () {
    $student = User::factory()->create();
    $student->assignRole('student');

    $registration = InternshipRegistration::factory()->create([
        'student_id' => $student->id,
    ]);
    $registration->setStatus('active');

    AttendanceLog::create([
        'registration_id' => $registration->id,
        'student_id' => $student->id,
        'date' => now()->format('Y-m-d'),
        'check_in_at' => now()->subHours(1),
    ]);

    $this->actingAs($student);

    Livewire::test(AttendanceManager::class)->call('clockIn')->assertOk();

    // Verify no new log was created (still only 1 log for today)
    expect(AttendanceLog::where('student_id', $student->id)->count())->toBe(1);
});
