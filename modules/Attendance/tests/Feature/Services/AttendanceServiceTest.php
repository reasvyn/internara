<?php

declare(strict_types=1);

namespace Modules\Attendance\Tests\Feature\Services;

use Modules\Attendance\Models\AttendanceLog;
use Modules\Attendance\Services\Contracts\AttendanceService;
use Modules\Exception\AppException;
use Modules\Internship\Models\InternshipPlacement;
use Modules\Internship\Models\InternshipRegistration;
use Modules\Permission\Models\Role;
use Modules\User\Models\User;

beforeEach(function () {
    Role::firstOrCreate(['name' => 'student', 'guard_name' => 'web']);
    $this->attendanceService = app(AttendanceService::class);
});

test('it can check in a student within the active period [STRS-01] [SYRS-F-401]', function () {
    $student = User::factory()->create();
    $student->assignRole('student');

    $placement = InternshipPlacement::factory()->create();

    $registration = InternshipRegistration::factory()->create([
        'student_id' => $student->id,
        'placement_id' => $placement->id,
        'start_date' => now()->subDay()->format('Y-m-d'),
        'end_date' => now()->addMonth()->format('Y-m-d'),
    ]);
    $registration->setStatus('active');

    // Success: Check-in
    $log = $this->attendanceService->checkIn($student->id);

    expect($log)->toBeInstanceOf(AttendanceLog::class);
    expect($log->date->format('Y-m-d'))->toBe(now()->format('Y-m-d'));

    $this->assertDatabaseHas('attendance_logs', [
        'id' => $log->id,
        'student_id' => $student->id,
    ]);
});

test('it throws exception when checking in outside the internship period [STRS-01] [SYRS-F-401]', function () {
    $student = User::factory()->create();
    $student->assignRole('student');

    $registration = InternshipRegistration::factory()->create([
        'student_id' => $student->id,
        'start_date' => now()->addDay()->format('Y-m-d'),
        'end_date' => now()->addMonth()->format('Y-m-d'),
    ]);
    $registration->setStatus('active');

    expect(fn () => $this->attendanceService->checkIn($student->id))->toThrow(
        AppException::class,
        'attendance::messages.outside_internship_period',
    );
});
