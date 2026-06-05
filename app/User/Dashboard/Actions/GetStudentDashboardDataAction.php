<?php

declare(strict_types=1);

namespace App\User\Dashboard\Actions;

use App\Assignment\Assignment\Enums\AssignmentStatus;
use App\Assignment\Assignment\Models\Assignment;
use App\Assignment\Submission\Enums\SubmissionStatus;
use App\Assignment\Submission\Models\Submission;
use App\Core\Actions\BaseAction;
use App\Enrollment\Models\Registration;
use App\Guidance\Handbook\Models\Handbook;
use App\Guidance\HandbookAcknowledgement\Models\HandbookAcknowledgement;
use App\Journals\Attendance\Enums\AttendanceStatus;
use App\Journals\Attendance\Models\Attendance;
use App\Journals\Logbook\Models\Logbook;
use App\User\Models\User;
use RuntimeException;

final class GetStudentDashboardDataAction extends BaseAction
{
    /**
     * @return array{
     *     registration: ?Registration,
     *     totalJournals: int,
     *     verifiedJournals: int,
     *     attendancePercent: float,
     *     assignmentSubmittedCount: int,
     *     assignmentTotalCount: int,
     *     handbookReadCount: int,
     *     handbookTotalCount: int
     * }
     */
    public function execute(string $userId): array
    {
        $user = User::find($userId);

        throw_unless($user, new RuntimeException('User not found.'));

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

            // Attendance calculation
            $totalAttendanceDays = Attendance::where('registration_id', $registration->id)->count();
            $presentDays = Attendance::where('registration_id', $registration->id)
                ->whereIn('status', [AttendanceStatus::PRESENT->value, AttendanceStatus::LATE->value])
                ->count();
            $attendancePercent = $totalAttendanceDays > 0 ? round(($presentDays / $totalAttendanceDays) * 100, 1) : 100.0;

            // Assignments calculation
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

        // Handbook reading statistics
        $handbookTotalCount = Handbook::where('is_active', true)->count();
        $handbookReadCount = HandbookAcknowledgement::where('user_id', $userId)->count();

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
