<?php

declare(strict_types=1);

namespace Modules\Attendance\Services;

use Modules\Attendance\Models\AttendanceLog;
use Modules\Attendance\Services\Contracts\AttendanceService as Contract;
use Modules\Exception\AppException;
use Modules\Internship\Services\Contracts\RegistrationService;
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
    public function __construct(
        protected RegistrationService $registrationService,
        AttendanceLog $model,
    ) {
        $this->setModel($model);
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

        // Check if there is an approved absence request for today
        $hasApprovedAbsence = \Modules\Attendance\Models\AbsenceRequest::query()
            ->where('student_id', $studentId)
            ->whereDate('date', today())
            ->currentStatus('approved')
            ->exists();

        if ($hasApprovedAbsence) {
            throw new AppException(
                userMessage: 'attendance::messages.cannot_check_in_with_approved_absence',
                code: 422,
            );
        }

        // Check if already checked in
        if ($this->getTodayLog($studentId)) {
            throw new AppException(
                userMessage: 'attendance::messages.already_checked_in',
                code: 422,
            );
        }

        // Find active registration
        $registration = $this->registrationService->first([
            'student_id' => $studentId,
            'latest_status' => 'active',
        ]);

        if (! $registration) {
            throw new AppException(
                userMessage: 'internship::messages.no_active_registration',
                code: 404,
            );
        }

        // Period Invariant: activities are restricted to assigned date range
        $todayStr = now()->format('Y-m-d');
        if (
            ($registration->start_date && $todayStr < $registration->start_date->format('Y-m-d')) ||
            ($registration->end_date && $todayStr > $registration->end_date->format('Y-m-d'))
        ) {
            throw new AppException(
                userMessage: 'attendance::messages.outside_internship_period',
                code: 403,
            );
        }

        /** @var AttendanceLog $log */
        $log = $this->create([
            'student_id' => $studentId,
            'registration_id' => $registration->id,
            'date' => $today->format('Y-m-d'),
            'check_in_at' => now(),
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
            throw new AppException(
                userMessage: 'attendance::messages.no_check_in_record',
                code: 404,
            );
        }

        if ($log->check_out_at) {
            throw new AppException(
                userMessage: 'attendance::messages.already_checked_out',
                code: 422,
            );
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

    /**
     * {@inheritdoc}
     */
    public function createAbsenceRequest(array $data): \Modules\Attendance\Models\AbsenceRequest
    {
        /** @var \Modules\Attendance\Models\AbsenceRequest $request */
        $request = \Modules\Attendance\Models\AbsenceRequest::create($data);
        $request->setStatus('pending', 'Absence request submitted by student.');

        return $request;
    }
}
