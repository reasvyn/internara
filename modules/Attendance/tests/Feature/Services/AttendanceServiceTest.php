<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Attendance\Models\AttendanceLog;
use Modules\Attendance\Services\Contracts\AttendanceService;
use Modules\Exception\AppException;
use Modules\Internship\Models\InternshipRegistration;
use Modules\Permission\Models\Role;
use Modules\User\Models\User;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'student', 'guard_name' => 'web']);
    $this->attendanceService = app(AttendanceService::class);
});

test('it can check in a student within the active period', function () {
    $student = User::factory()->create();
    $student->assignRole('student');

    $registration = InternshipRegistration::factory()->create([
        'student_id' => $student->id,
        'start_date' => now()->subDay()->format('Y-m-d'),
        'end_date' => now()->addMonth()->format('Y-m-d'),
    ]);
    $registration->setStatus('active');

    $log = $this->attendanceService->checkIn($student->id);

    expect($log)->toBeInstanceOf(AttendanceLog::class);
    expect($log->date->format('Y-m-d'))->toBe(now()->format('Y-m-d'));
    $this->assertDatabaseHas('attendance_logs', [
        'id' => $log->id,
        'student_id' => $student->id,
    ]);
});

test('it throws exception when checking in before the internship period starts', function () {
    $student = User::factory()->create();
    $student->assignRole('student');

    $registration = InternshipRegistration::factory()->create([
        'student_id' => $student->id,
        'start_date' => now()->addDay()->format('Y-m-d'), // Starts tomorrow
        'end_date' => now()->addMonth()->format('Y-m-d'),
    ]);
    $registration->setStatus('active');

    expect(fn () => $this->attendanceService->checkIn($student->id))
        ->toThrow(AppException::class, 'attendance::messages.outside_internship_period');
});

test('it throws exception when checking in after the internship period ends', function () {
    $student = User::factory()->create();
    $student->assignRole('student');

    $registration = InternshipRegistration::factory()->create([
        'student_id' => $student->id,
        'start_date' => now()->subMonth()->format('Y-m-d'),
        'end_date' => now()->subDay()->format('Y-m-d'), // Ended yesterday
    ]);
    $registration->setStatus('active');

    expect(fn () => $this->attendanceService->checkIn($student->id))
        ->toThrow(AppException::class, 'attendance::messages.outside_internship_period');
});
