<?php

declare(strict_types=1);

namespace App\Assessment\Actions;

use App\Assessment\Models\Assessment;
use App\Assignment\Submission\Models\Submission;
use App\Core\Actions\BaseAction;
use App\Reports\Report\Models\Report;
use Illuminate\Support\Facades\DB;

final class AutoCalculateAssessmentAction extends BaseAction
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

        $report = Report::where('registration_id', $registrationId)
            ->where('status', 'approved')
            ->first();

        $scoresData = $assessment->scores_data ?? [];
        $scoresData['auto'] = [
            'avg_submission_score' => $avgSubmissionScore
                ? round((float) $avgSubmissionScore, 1)
                : null,
            'logbook_completeness' => $logbookCompleteness,
            'report_score' => $report?->score,
        ];

        $assessment->update(['scores_data' => $scoresData]);

        return $assessment->fresh();
    }
}
