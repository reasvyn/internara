<?php

declare(strict_types=1);

namespace App\Domain\Internship\Actions;

use App\Domain\Assessment\Models\Assessment;
use App\Domain\Assignment\Models\Submission;
use App\Domain\Attendance\Models\Attendance;
use App\Domain\Core\Actions\BaseAction;
use App\Domain\Internship\Models\Internship;
use App\Domain\Mentor\Models\SupervisionLog;
use App\Domain\Registration\Models\Registration;

final class CheckCloseReadinessAction extends BaseAction
{
    /**
     * @return array<string, array{passed: bool, total: int, pending: int, message: string}>
     */
    public function execute(Internship $internship): array
    {
        $registrations = Registration::where('internship_id', $internship->id)
            ->whereHas('statuses', fn ($q) => $q->where('name', 'active'))
            ->pluck('id');

        return [
            'assessments' => $this->checkAssessments($registrations),
            'submissions' => $this->checkSubmissions($registrations),
            'supervision_logs' => $this->checkSupervisionLogs($registrations),
            'attendance' => $this->checkAttendance($registrations),
        ];
    }

    private function checkAssessments($registrationIds): array
    {
        $total = Assessment::whereIn('registration_id', $registrationIds)->count();
        $pending = Assessment::whereIn('registration_id', $registrationIds)
            ->whereNull('finalized_at')
            ->count();

        return [
            'passed' => $pending === 0,
            'total' => $total,
            'pending' => $pending,
            'message' => $pending === 0
                ? 'All assessments finalized.'
                : "{$pending} assessment(s) not yet finalized.",
        ];
    }

    private function checkSubmissions($registrationIds): array
    {
        $total = Submission::whereIn('registration_id', $registrationIds)->count();
        $pending = Submission::whereIn('registration_id', $registrationIds)
            ->whereIn('status', ['draft', 'submitted', 'revision_required'])
            ->count();

        return [
            'passed' => $pending === 0,
            'total' => $total,
            'pending' => $pending,
            'message' => $pending === 0
                ? 'All submissions graded or verified.'
                : "{$pending} submission(s) pending grading.",
        ];
    }

    private function checkSupervisionLogs($registrationIds): array
    {
        $total = SupervisionLog::whereIn('registration_id', $registrationIds)->count();
        $pending = SupervisionLog::whereIn('registration_id', $registrationIds)
            ->where('is_verified', false)
            ->count();

        return [
            'passed' => $pending === 0,
            'total' => $total,
            'pending' => $pending,
            'message' => $pending === 0
                ? 'All supervision logs verified.'
                : "{$pending} supervision log(s) not yet verified.",
        ];
    }

    private function checkAttendance($registrationIds): array
    {
        $total = Attendance::whereIn('registration_id', $registrationIds)->count();
        $pending = Attendance::whereIn('registration_id', $registrationIds)
            ->where('is_verified', false)
            ->count();

        return [
            'passed' => $pending === 0,
            'total' => $total,
            'pending' => $pending,
            'message' => $pending === 0
                ? 'All attendance records verified.'
                : "{$pending} attendance record(s) not yet verified.",
        ];
    }
}
