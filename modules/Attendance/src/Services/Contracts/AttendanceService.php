<?php

declare(strict_types=1);

namespace Modules\Attendance\Services\Contracts;

use Modules\Attendance\Models\AttendanceLog;
use Modules\Shared\Services\Contracts\EloquentQuery;

/**
 * Interface AttendanceService
 *
 * Handles business logic for student attendance tracking.
 *
 * @extends EloquentQuery<AttendanceLog>
 */
interface AttendanceService extends EloquentQuery
{
    /**
     * Record a check-in for the current student.
     *
     * @throws \Exception If already checked in.
     */
    public function checkIn(string $studentId): AttendanceLog;

    /**
     * Record a check-out for the current student's today log.
     *
     * @throws \Exception If no check-in record found or already checked out.
     */
    public function checkOut(string $studentId): AttendanceLog;

    /**
     * Get the attendance log for a student for a specific date.
     */
    public function getTodayLog(string $studentId): ?AttendanceLog;

    /**
     * Get attendance count for a registration.
     */
    public function getAttendanceCount(string $registrationId, ?string $status = null): int;

    /**
     * Create a new absence request.
     */
    public function createAbsenceRequest(array $data): \Modules\Attendance\Models\AbsenceRequest;
}
