<?php

declare(strict_types=1);

namespace Modules\Assessment\Services;

use Carbon\Carbon;
use Modules\Assessment\Services\Contracts\ComplianceService as Contract;
use Modules\Attendance\Services\Contracts\AttendanceService;
use Modules\Internship\Services\Contracts\RegistrationService;
use Modules\Journal\Services\Contracts\JournalService;
use Modules\Shared\Services\BaseService;

class ComplianceService extends BaseService implements Contract
{
    public function __construct(
        protected RegistrationService $registrationService,
        protected AttendanceService $attendanceService,
        protected JournalService $journalService,
    ) {}

    /**
     * {@inheritdoc}
     */
    public function calculateScore(string $registrationId): array
    {
        /** @var \Modules\Internship\Models\InternshipRegistration|null $registration */
        $registration = $this->registrationService->find($registrationId);

        if (! $registration) {
            throw new \Illuminate\Database\Eloquent\ModelNotFoundException()->setModel(
                \Modules\Internship\Models\InternshipRegistration::class,
                [$registrationId],
            );
        }

        $startDate = $registration->start_date;
        $endDate = $registration->end_date;

        if (! $startDate || ! $endDate) {
            return $this->emptyScore();
        }

        $totalDays = $this->calculateWorkingDays($startDate, $endDate);

        // If the internship is still ongoing, we cap totalDays to today
        $effectiveTotalDays = min(
            $totalDays,
            $this->calculateWorkingDays($startDate, Carbon::now()->min($endDate)),
        );

        if ($effectiveTotalDays <= 0) {
            return $this->emptyScore();
        }

        $attendedDays = $this->attendanceService->getAttendanceCount($registrationId);
        $approvedJournals = $this->journalService->getJournalCount($registrationId, 'approved');

        $attendanceScore = min(100, ($attendedDays / $effectiveTotalDays) * 100);
        $journalScore = min(100, ($approvedJournals / $effectiveTotalDays) * 100);

        // Retrieve dynamic weights from settings (default to 0.5 each)
        $attendanceWeight = (float) setting('compliance_attendance_weight', 0.5);
        $journalWeight = (float) setting('compliance_journal_weight', 0.5);

        return [
            'attendance_score' => round($attendanceScore, 2),
            'journal_score' => round($journalScore, 2),
            'final_score' => round(
                $attendanceScore * $attendanceWeight + $journalScore * $journalWeight,
                2,
            ),
            'total_days' => $effectiveTotalDays,
            'attended_days' => $attendedDays,
            'approved_journals' => $approvedJournals,
        ];
    }

    /**
     * Calculate working days (Mon-Fri) between two dates.
     */
    protected function calculateWorkingDays(Carbon $start, Carbon $end): int
    {
        if ($start->gt($end)) {
            return 0;
        }

        return (int) $start->diffInDaysFiltered(
            fn (Carbon $date) => ! $date->isWeekend(),
            $end->addDay(),
        );
    }

    /**
     * Return a zeroed score array.
     */
    protected function emptyScore(): array
    {
        return [
            'attendance_score' => 0.0,
            'journal_score' => 0.0,
            'final_score' => 0.0,
            'total_days' => 0,
            'attended_days' => 0,
            'approved_journals' => 0,
        ];
    }
}
