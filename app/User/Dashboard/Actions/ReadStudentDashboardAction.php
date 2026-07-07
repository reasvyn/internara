<?php

declare(strict_types=1);

namespace App\User\Dashboard\Actions;

use App\Assignment\Enums\AssignmentStatus;
use App\Assignment\Models\Assignment;
use App\Assignment\Submission\Enums\SubmissionStatus;
use App\Assignment\Submission\Models\Submission;
use App\Core\Actions\BaseReadAction;
use App\Core\Exceptions\RejectedException;
use App\Document\Models\Document;
use App\Journals\Attendance\Enums\AttendanceStatus;
use App\Journals\Attendance\Models\Attendance;
use App\Journals\Logbook\Models\Logbook;
use App\User\Models\User;
use Illuminate\Support\Facades\Cache;
use Spatie\Activitylog\Models\Activity;

final class ReadStudentDashboardAction extends BaseReadAction
{
    public function execute(string $userId): array
    {
        return Cache::remember(
            config('cache-keys.dashboard_student') . $userId,
            300,
            fn() => $this->computeData($userId),
        );
    }

    private function computeData(string $userId): array
    {
        $user = User::find($userId);

        if (!$user) {
            throw new RejectedException('User not found.');
        }

        $registration = $user->getActiveRegistration();

        $totalJournals = 0;
        $verifiedJournals = 0;
        $attendancePercent = 100.0;
        $assignmentSubmittedCount = 0;
        $assignmentTotalCount = 0;

        if ($registration) {
            $totalJournals = Logbook::where('user_id', $userId)
                ->where('registration_id', $registration->id)
                ->count();

            $verifiedJournals = Logbook::where('user_id', $userId)
                ->where('registration_id', $registration->id)
                ->where('is_verified', true)
                ->count();

            $totalAttendanceDays = Attendance::where('registration_id', $registration->id)->count();
            $presentDays = Attendance::where('registration_id', $registration->id)
                ->whereIn('status', [
                    AttendanceStatus::PRESENT->value,
                    AttendanceStatus::LATE->value,
                ])
                ->count();
            $attendancePercent =
                $totalAttendanceDays > 0
                    ? round(($presentDays / $totalAttendanceDays) * 100, 1)
                    : 100.0;

            $assignmentTotalCount = Assignment::where('internship_id', $registration->internship_id)
                ->where('status', AssignmentStatus::PUBLISHED->value)
                ->count();
            $assignmentSubmittedCount = Submission::where('registration_id', $registration->id)
                ->whereIn('status', [
                    SubmissionStatus::SUBMITTED->value,
                    SubmissionStatus::VERIFIED->value,
                    SubmissionStatus::GRADED->value,
                ])
                ->count();
        }

        $handbookTotalCount = Document::where('type', 'handbook')
            ->where('is_active', true)
            ->count();
        $handbookReadCount = Activity::causedBy($user)
            ->inLog('document')
            ->forEvent('acknowledged')
            ->whereHas('subject', fn($q) => $q->where('type', 'handbook'))
            ->count();

        return [
            'registration' => $registration,
            'totalJournals' => $totalJournals,
            'verifiedJournals' => $verifiedJournals,
            'attendancePercent' => (float) $attendancePercent,
            'assignmentSubmittedCount' => $assignmentSubmittedCount,
            'assignmentTotalCount' => $assignmentTotalCount,
            'handbookReadCount' => $handbookReadCount,
            'handbookTotalCount' => $handbookTotalCount,
        ];
    }
}
