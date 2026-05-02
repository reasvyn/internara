<?php

declare(strict_types=1);

namespace Modules\Attendance\Services\Contracts;

use Illuminate\Pagination\Paginator;
use Modules\Attendance\Models\AttendanceLog;

interface AttendanceService
{
    public function findById(string $id): ?AttendanceLog;

    public function clockIn(string $studentId): AttendanceLog;

    public function clockOut(string $attendanceId): AttendanceLog;

    public function getStudentAttendance(string $studentId, array $filters = []): array;

    public function paginate(int $perPage = 15): Paginator;
}
