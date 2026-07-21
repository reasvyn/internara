<?php

declare(strict_types=1);

namespace App\Assessment\Actions;

use App\Assessment\Models\Assessment;
use App\Assignment\Submission\Models\Submission;
use App\Core\Actions\BaseCommandAction;
use App\Journals\Attendance\Models\Attendance;
use App\Journals\MonitoringVisit\Models\MonitoringVisit;
use App\Journals\SupervisionLog\Models\SupervisionLog;
use App\Reports\Report\Models\Report;
use Illuminate\Support\Facades\DB;

final class AutoCalculateAssessmentAction extends BaseCommandAction
{
    public function execute(Assessment $assessment): Assessment
    {
        if ($assessment->finalized_at !== null) {
            return $assessment;
        }

        $registrationId = $assessment->registration_id;

        $avgSubmissionScore = Submission::where('registration_id', $registrationId)
            ->where('status', 'verified')
            ->whereNotNull('score')
            ->avg('score');

        $totalLogbooks = DB::table('logbooks')->where('registration_id', $registrationId)->count();

        $submittedLogbooks = DB::table('logbooks')
            ->where('registration_id', $registrationId)
            ->whereIn('status', ['submitted', 'verified'])
            ->count();

        $logbookCompleteness =
            $totalLogbooks > 0 ? round(($submittedLogbooks / $totalLogbooks) * 100, 1) : 0;

        $totalAttendance = Attendance::where('registration_id', $registrationId)->count();
        $presentAttendance = Attendance::where('registration_id', $registrationId)
            ->whereIn('status', ['present', 'late'])
            ->count();
        $attendanceRate = $totalAttendance > 0
            ? round(($presentAttendance / $totalAttendance) * 100, 1)
            : 0;

        $totalSupervisionLogs = SupervisionLog::where('registration_id', $registrationId)->count();
        $reviewedSupervisionLogs = SupervisionLog::where('registration_id', $registrationId)
            ->whereIn('status', ['reviewed', 'acknowledged'])
            ->count();
        $supervisionCompleteness = $totalSupervisionLogs > 0
            ? round(($reviewedSupervisionLogs / $totalSupervisionLogs) * 100, 1)
            : 0;

        $totalMonitoringVisits = MonitoringVisit::where('registration_id', $registrationId)->count();
        $verifiedVisits = MonitoringVisit::where('registration_id', $registrationId)
            ->where('is_verified', true)
            ->count();
        $visitCompleteness = $totalMonitoringVisits > 0
            ? round(($verifiedVisits / $totalMonitoringVisits) * 100, 1)
            : 0;

        $report = Report::where('registration_id', $registrationId)
            ->where('status', 'approved')
            ->first();

        $scoresData = $assessment->scores_data ?? [];
        $scoresData['auto'] = [
            'avg_submission_score' => $avgSubmissionScore
                ? round((float) $avgSubmissionScore, 1)
                : null,
            'logbook_completeness' => $logbookCompleteness,
            'attendance_rate' => $attendanceRate,
            'supervision_completeness' => $supervisionCompleteness,
            'monitoring_visit_completeness' => $visitCompleteness,
            'report_score' => $report?->final_score,
        ];

        $assessment->update(['scores_data' => $scoresData]);

        $this->log('assessment_auto_calculated', $assessment, [
            'registration_id' => $registrationId,
        ]);

        return $assessment->fresh();
    }
}
