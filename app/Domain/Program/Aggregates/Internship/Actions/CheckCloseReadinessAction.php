<?php

declare(strict_types=1);

namespace App\Domain\Program\Aggregates\Internship\Actions;

use App\Domain\Assessment\Aggregates\Assessment\Models\Assessment;
use App\Domain\Assignment\Aggregates\Submission\Models\Submission;
use App\Domain\Certification\Aggregates\Certificate\Enums\CertificateStatus;
use App\Domain\Certification\Aggregates\Certificate\Models\Certificate;
use App\Domain\Core\Actions\BaseAction;
use App\Domain\Enrollment\Models\Registration;
use App\Domain\Guidance\Aggregates\SupervisionLog\Models\SupervisionLog;
use App\Domain\Journals\Aggregates\Attendance\Models\Attendance;
use App\Domain\Program\Aggregates\Internship\Models\Internship;

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
            'certificates' => $this->checkCertificates($registrations),
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

    private function checkCertificates($registrationIds): array
    {
        $total = Certificate::whereIn('registration_id', $registrationIds)->count();
        $pending = Certificate::whereIn('registration_id', $registrationIds)
            ->where('status', '!=', CertificateStatus::ISSUED)
            ->count();

        return [
            'passed' => $pending === 0 && $total > 0,
            'total' => $total,
            'pending' => $pending,
            'message' => match (true) {
                $total === 0 => 'No certificates issued.',
                $pending === 0 => 'All certificates issued.',
                default => "{$pending} certificate(s) not yet issued.",
            },
        ];
    }
}
