<?php

declare(strict_types=1);

namespace Modules\Attendance\Tests\Feature\Services;

use Modules\Attendance\Models\AttendanceLog;
use Modules\Attendance\Services\Contracts\AttendanceService;
use Modules\Exception\AppException;
use Modules\Internship\Services\Contracts\RegistrationService;
use Modules\Permission\Models\Role;
use Modules\User\Models\User;

beforeEach(function () {
    Role::firstOrCreate(['name' => 'student', 'guard_name' => 'web']);
    $this->attendanceService = app(AttendanceService::class);
});

test('it can check in a student within the active period [STRS-01] [SYRS-F-401]', function () {
    $student = User::factory()->create();
    $student->assignRole('student');

    // Mock RegistrationService to avoid direct dependency on Internship models
    $registration = (object) [
        'id' => 'reg-uuid',
        'student_id' => $student->id,
        'start_date' => now()->subDay(),
        'end_date' => now()->addMonth(),
        'academic_year' => '2025/2026',
    ];

    $registrationService = mock(RegistrationService::class);
    $registrationService->shouldReceive('first')->andReturn($registration);
    $this->app->instance(RegistrationService::class, $registrationService);

    // Success: Check-in
    $log = $this->attendanceService->checkIn($student->id);

    expect($log)->toBeInstanceOf(AttendanceLog::class);
    expect($log->date->format('Y-m-d'))->toBe(now()->format('Y-m-d'));

    $this->assertDatabaseHas('attendance_logs', [
        'id' => $log->id,
        'student_id' => $student->id,
    ]);
});

test(
    'it throws exception when checking in outside the internship period [STRS-01] [SYRS-F-401]',
    function () {
        $student = User::factory()->create();
        $student->assignRole('student');

        $registration = (object) [
            'id' => 'reg-uuid',
            'student_id' => $student->id,
            'start_date' => now()->addDay(),
            'end_date' => now()->addMonth(),
            'academic_year' => '2025/2026',
        ];

        $registrationService = mock(RegistrationService::class);
        $registrationService->shouldReceive('first')->andReturn($registration);
        $this->app->instance(RegistrationService::class, $registrationService);

        expect(fn() => $this->attendanceService->checkIn($student->id))->toThrow(
            AppException::class,
            'attendance::messages.outside_internship_period',
        );
    },
);
