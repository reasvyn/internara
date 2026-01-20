<?php

declare(strict_types=1);

namespace Modules\Assessment\Services;

use Carbon\Carbon;
use Modules\Assessment\Services\Contracts\ComplianceService as Contract;
use Modules\Attendance\Services\Contracts\AttendanceService;
use Modules\Internship\Models\InternshipRegistration;
use Modules\Journal\Services\Contracts\JournalService;

class ComplianceService implements Contract
{
    public function __construct(
        protected AttendanceService $attendanceService,
        protected JournalService $journalService
    ) {}

    /**
     * {@inheritdoc}
     */
    public function calculateScore(string $registrationId): array
    {
        $registration = InternshipRegistration::with('internship')->findOrFail($registrationId);
        $internship = $registration->internship;

        $totalDays = $this->calculateWorkingDays($internship->date_start, $internship->date_finish);

        // If the internship is still ongoing, we might want to cap totalDays
        // to today's date if date_finish is in the future.
        $effectiveTotalDays = min(
            $totalDays,
            $this->calculateWorkingDays($internship->date_start, Carbon::now()->min($internship->date_finish))
        );

        if ($effectiveTotalDays <= 0) {
            return $this->emptyScore();
        }

        $attendedDays = $this->attendanceService->getAttendanceCount($registrationId);
        $approvedJournals = $this->journalService->getJournalCount($registrationId, 'approved');

        $attendanceScore = min(100, ($attendedDays / $effectiveTotalDays) * 100);
        $journalScore = min(100, ($approvedJournals / $effectiveTotalDays) * 100);

        return [
            'attendance_score' => round($attendanceScore, 2),
            'journal_score' => round($journalScore, 2),
            'final_score' => round(($attendanceScore * 0.5) + ($journalScore * 0.5), 2),
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

        return (int) $start->diffInDaysFiltered(fn (Carbon $date) => ! $date->isWeekend(), $end->addDay());
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
