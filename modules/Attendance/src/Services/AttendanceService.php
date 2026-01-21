<?php

declare(strict_types=1);

namespace Modules\Attendance\Services;

use Modules\Attendance\Models\AttendanceLog;
use Modules\Attendance\Services\Contracts\AttendanceService as Contract;
use Modules\Internship\Services\Contracts\InternshipRegistrationService;
use Modules\Shared\Services\EloquentQuery;

/**
 * Class AttendanceService
 *
 * Handles business logic for student attendance.
 */
class AttendanceService extends EloquentQuery implements Contract
{
    /**
     * AttendanceService constructor.
     */
    public function __construct()
    {
        $this->setModel(new AttendanceLog);
        $this->setSortable(['date', 'check_in_at', 'created_at']);
    }

    /**
     * {@inheritDoc}
     */
    protected function applyFilters(&$query, array &$filters): void
    {
        if (isset($filters['date'])) {
            $query->whereDate('date', $filters['date']);
            unset($filters['date']);
        }

        if (isset($filters['date_from'])) {
            $query->where('date', '>=', $filters['date_from']);
            unset($filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('date', '<=', $filters['date_to']);
            unset($filters['date_to']);
        }

        parent::applyFilters($query, $filters);
    }

    /**
     * {@inheritDoc}
     */
    public function checkIn(string $studentId): AttendanceLog
    {
        $today = now()->startOfDay();

        // Check if already checked in
        if ($this->getTodayLog($studentId)) {
            throw new \Exception(__('attendance::messages.already_checked_in'));
        }

        // Find active registration
        $registration = app(InternshipRegistrationService::class)->first([
            'student_id' => $studentId,
            'latest_status' => 'active',
        ]);

        if (! $registration) {
            throw new \Exception(__('internship::messages.no_active_registration'));
        }

        /** @var AttendanceLog $log */
        $log = $this->create([
            'student_id' => $studentId,
            'registration_id' => $registration->id,
            'date' => $today->format('Y-m-d'),
            'check_in_at' => now(),
            'academic_year' => setting('active_academic_year', '2025/2026'),
        ]);

        // Determine status (Late vs Present) based on settings
        $lateThreshold = setting('attendance_late_threshold', '08:00');
        [$hour, $minute] = explode(':', $lateThreshold);

        $startTime = now()->setTime((int) $hour, (int) $minute, 0);
        $status = now()->greaterThan($startTime) ? 'late' : 'present';

        $log->setStatus($status, 'Checked in at '.now()->format('H:i:s'));

        return $log;
    }

    /**
     * {@inheritDoc}
     */
    public function checkOut(string $studentId): AttendanceLog
    {
        $log = $this->getTodayLog($studentId);

        if (! $log) {
            throw new \Exception(__('attendance::messages.no_check_in_record'));
        }

        if ($log->check_out_at) {
            throw new \Exception(__('attendance::messages.already_checked_out'));
        }

        $log->update([
            'check_out_at' => now(),
        ]);

        return $log;
    }

    /**
     * {@inheritdoc}
     */
    public function getTodayLog(string $studentId): ?AttendanceLog
    {
        return $this->model
            ->newQuery()
            ->where('student_id', $studentId)
            ->whereDate('date', today())
            ->first();
    }

    /**
     * {@inheritdoc}
     */
    public function getAttendanceCount(string $registrationId, ?string $status = null): int
    {
        $query = $this->model->newQuery()->where('registration_id', $registrationId);

        if ($status) {
            $query->currentStatus($status);
        }

        return $query->count();
    }
}
