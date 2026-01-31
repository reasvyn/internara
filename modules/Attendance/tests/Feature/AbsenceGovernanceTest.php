<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Attendance\Models\AbsenceRequest;
use Modules\Attendance\Services\Contracts\AttendanceService;
use Modules\Exception\AppException;
use Modules\Internship\Models\InternshipRegistration;
use Modules\User\Models\User;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->attendanceService = app(AttendanceService::class);
});

test('it prevents check-in if an approved absence request exists for today', function () {
    $student = User::factory()->create();
    $registration = InternshipRegistration::factory()->create([
        'student_id' => $student->id,
        'start_date' => now()->subDay()->format('Y-m-d'),
        'end_date' => now()->addMonth()->format('Y-m-d'),
    ]);

    // Create approved absence for today
    $absence = AbsenceRequest::create([
        'student_id' => $student->id,
        'registration_id' => $registration->id,
        'date' => now()->format('Y-m-d'),
        'type' => 'sick',
        'reason' => 'Fever',
    ]);
    $absence->setStatus('approved');

    expect(fn () => $this->attendanceService->checkIn($student->id))->toThrow(
        AppException::class,
        'attendance::messages.cannot_check_in_with_approved_absence',
    );
});
