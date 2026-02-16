<?php

declare(strict_types=1);

namespace Modules\Attendance\Services;

use Modules\Attendance\Enums\AttendanceStatus;
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

        if (isset($filters['status'])) {
            $query->currentStatus($filters['status']);
            unset($filters['status']);
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
        return $this->recordAttendance($studentId, [
            'date' => now()->format('Y-m-d'),
            'status' => AttendanceStatus::PRESENT->value,
            'check_in_at' => now(),
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function recordAttendance(string $studentId, array $data): AttendanceLog
    {
        $settingService = app(\Modules\Setting\Services\Contracts\SettingService::class);
        $guidanceService = app(\Modules\Guidance\Services\Contracts\HandbookService::class);

        if (
            $settingService->getValue('feature_guidance_enabled', true) &&
            ! $guidanceService->hasCompletedMandatory($studentId)
        ) {
            throw new AppException(
                userMessage: 'guidance::messages.must_complete_guidance',
                code: 403,
            );
        }

        $date = $data['date'] ?? now()->format('Y-m-d');
        if ($date instanceof \DateTimeInterface) {
            $date = $date->format('Y-m-d');
        }
        $status = $data['status'] ?? AttendanceStatus::PRESENT->value;

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

        // Check for approved absence requests for this date
        $hasApprovedAbsence = \Modules\Attendance\Models\AbsenceRequest::query()
            ->where('student_id', $studentId)
            ->whereDate('date', $date)
            ->currentStatus('approved')
            ->exists();

        if ($hasApprovedAbsence) {
            throw new AppException(
                userMessage: 'attendance::messages.cannot_check_in_with_approved_absence',
                code: 403,
            );
        }

        if (
            ($registration->start_date && $date < $registration->start_date->format('Y-m-d')) ||
            ($registration->end_date && $date > $registration->end_date->format('Y-m-d'))
        ) {
            throw new AppException(
                userMessage: 'attendance::messages.outside_internship_period',
                code: 403,
            );
        }

        // 4. Persistence: Update existing or create new
        /** @var AttendanceLog $log */
        $log = $this->model->newQuery()->updateOrCreate(
            ['student_id' => $studentId, 'date' => $date],
            [
                'registration_id' => $registration->id,
                'academic_year' => $registration->academic_year,
                'check_in_at' => $data['check_in_at'] ?? null,
                'check_out_at' => $data['check_out_at'] ?? null,
                'notes' => $data['notes'] ?? null,
            ],
        );

        // 5. Apply Status
        $reason = $data['reason'] ?? 'Attendance recorded via flexible entry.';
        if ($status === AttendanceStatus::PRESENT->value && ! empty($data['check_in_at'])) {
            $lateThreshold = setting('attendance_late_threshold', '08:00');
            [$hour, $minute] = explode(':', $lateThreshold);
            $startTime = \Illuminate\Support\Carbon::parse($date)->setTime(
                (int) $hour,
                (int) $minute,
                0,
            );

            if (\Illuminate\Support\Carbon::parse($data['check_in_at'])->greaterThan($startTime)) {
                $reason .= ' (Late)';
            }
        }

        $log->setStatus($status, $reason);

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
