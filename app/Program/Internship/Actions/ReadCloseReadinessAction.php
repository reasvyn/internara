<?php

declare(strict_types=1);

namespace App\Program\Internship\Actions;

use App\Assessment\Models\Assessment;
use App\Assignment\Submission\Enums\SubmissionStatus;
use App\Assignment\Submission\Models\Submission;
use App\Certification\Certificate\Enums\CertificateStatus;
use App\Certification\Certificate\Models\Certificate;
use App\Core\Actions\BaseReadAction;
use App\Enrollment\Registration\Models\Registration;
use App\Journals\Attendance\Models\Attendance;
use App\Journals\SupervisionLog\Models\SupervisionLog;
use App\Program\Internship\Models\Internship;

final class ReadCloseReadinessAction extends BaseReadAction
{
    public function execute(Internship $internship): array
    {
        $registrations = Registration::where('internship_id', $internship->id)
            ->where('status', 'active')
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
            ->whereIn('status', [SubmissionStatus::DRAFT->value, SubmissionStatus::SUBMITTED->value, SubmissionStatus::REVISION_REQUIRED->value])
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
