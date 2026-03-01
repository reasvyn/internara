<?php

declare(strict_types=1);

namespace Modules\Attendance\Tests\Feature\Services;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Attendance\Models\AttendanceLog;
use Modules\Attendance\Services\Contracts\AttendanceService;
use Modules\Exception\AppException;
use Modules\Internship\Models\InternshipPlacement;
use Modules\Internship\Models\InternshipRegistration;
use Modules\Permission\Models\Role;
use Modules\User\Models\User;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'student', 'guard_name' => 'web']);
    $this->attendanceService = app(AttendanceService::class);
});

test('it can check in a student within the active period and geofence radius', function () {
    $student = User::factory()->create();
    $student->assignRole('student');

    // SMK Negeri 1 Jakarta Coordinates (Example center)
    $lat = -6.1647;
    $lng = 106.8324;

    $placement = InternshipPlacement::factory()->create([
        'latitude' => $lat,
        'longitude' => $lng,
    ]);

    $registration = InternshipRegistration::factory()->create([
        'student_id' => $student->id,
        'placement_id' => $placement->id,
        'start_date' => now()->subDay()->format('Y-m-d'),
        'end_date' => now()->addMonth()->format('Y-m-d'),
    ]);
    $registration->setStatus('active');

    // 1. Success: Check-in within 100m (SMK 1 to Monas area is close)
    $log = $this->attendanceService->checkIn($student->id, -6.165, 106.833);

    expect($log)->toBeInstanceOf(AttendanceLog::class);
    expect($log->date->format('Y-m-d'))->toBe(now()->format('Y-m-d'));

    $this->assertDatabaseHas('attendance_logs', [
        'id' => $log->id,
        'student_id' => $student->id,
        'latitude' => -6.165,
        'longitude' => 106.833,
    ]);
});

test(
    'it rejects check-in if student is outside the 500m geofence radius (Haversine audit)',
    function () {
        $student = User::factory()->create();
        $student->assignRole('student');

        // SMK Negeri 1 Jakarta
        $centerLat = -6.1647;
        $centerLng = 106.8324;

        $placement = InternshipPlacement::factory()->create([
            'latitude' => $centerLat,
            'longitude' => $centerLng,
        ]);

        $registration = InternshipRegistration::factory()->create([
            'student_id' => $student->id,
            'placement_id' => $placement->id,
            'start_date' => now()->subDay()->format('Y-m-d'),
            'end_date' => now()->addMonth()->format('Y-m-d'),
        ]);
        $registration->setStatus('active');

        // Attempt check-in from ~1km away (Gambir Station area)
        $farLat = -6.1767;
        $farLng = 106.8306;

        expect(fn () => $this->attendanceService->checkIn($student->id, $farLat, $farLng))->toThrow(
            AppException::class,
            'attendance::messages.out_of_range',
        );
    },
);

test('it throws exception when checking in outside the internship period', function () {
    $student = User::factory()->create();
    $student->assignRole('student');

    $registration = InternshipRegistration::factory()->create([
        'student_id' => $student->id,
        'start_date' => now()->addDay()->format('Y-m-d'),
        'end_date' => now()->addMonth()->format('Y-m-d'),
    ]);
    $registration->setStatus('active');

    expect(fn () => $this->attendanceService->checkIn($student->id, 0, 0))->toThrow(
        AppException::class,
        'attendance::messages.outside_internship_period',
    );
});
