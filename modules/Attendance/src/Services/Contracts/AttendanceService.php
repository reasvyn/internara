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
     * Records the temporal start of a student's vocational daily shift.
     *
     * Verifies that the student is not already active and captures
     * the authoritative timestamp for presence auditing.
     *
     * @throws \Modules\Exception\AppException If check-in collision detected.
     */
    public function checkIn(string $studentId): AttendanceLog;

    /**
     * Records the temporal conclusion of a student's vocational daily shift.
     *
     * Matches the request with the current day's check-in record,
     * calculating the total duration of presence.
     *
     * @throws \Modules\Exception\AppException If no active session found.
     */
    public function checkOut(string $studentId): AttendanceLog;

    /**
     * Manually persists an attendance record with administrative context.
     *
     * Used for corrective logging or recording non-standard presence
     * (e.g., Sick leave, Institutional events).
     */
    public function recordAttendance(string $studentId, array $data): AttendanceLog;

    /**
     * Retrieves the authoritative presence record for the current date.
     */
    public function getTodayLog(string $studentId): ?AttendanceLog;

    /**
     * Aggregates attendance telemetry for a specific registration.
     */
    public function getAttendanceCount(string $registrationId, ?string $status = null): int;

    /**
     * Initiates a request for authorized absence.
     *
     * This method orchestrates the documentation required to justify
     * non-presence, satisfying institutional compliance rules.
     */
    public function createAbsenceRequest(array $data): \Modules\Attendance\Models\AbsenceRequest;
}
