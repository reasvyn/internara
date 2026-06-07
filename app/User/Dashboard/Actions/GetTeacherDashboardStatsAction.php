<?php

declare(strict_types=1);

namespace App\User\Dashboard\Actions;

use App\Assignment\Submission\Enums\SubmissionStatus;
use App\Assignment\Submission\Models\Submission;
use App\Core\Actions\BaseAction;
use App\Enrollment\Models\Registration;
use App\Guidance\SupervisionLog\Models\SupervisionLog;
use App\Incident\IncidentReport\Enums\IncidentStatus;
use App\Incident\IncidentReport\Models\IncidentReport;
use App\Journals\Logbook\Models\Logbook;
use Illuminate\Support\Facades\Auth;

final class GetTeacherDashboardStatsAction extends BaseAction
{
    /** @return array{supervisedStudents: int, pendingJournals: int, activeCompanies: int, ungradedSubmissions: int, supervisionLogsCount: int, unresolvedIncidents: int} */
    public function execute(): array
    {
        $userId = Auth::id();

        $supervisedStudents = Registration::where('status', 'active')
            ->whereHas('mentors', fn ($q) => $q->where('user_id', $userId))
            ->count();

        $pendingJournals = Logbook::where('status', 'submitted')
            ->whereHas(
                'registration',
                fn ($q) => $q
                    ->where('status', 'active')
                    ->whereHas('mentors', fn ($q) => $q->where('user_id', $userId)),
            )
            ->count();

        $activeCompanies = Registration::where('status', 'active')
            ->whereHas('mentors', fn ($q) => $q->where('user_id', $userId))
            ->whereHas('placement.company')
            ->get()
            ->pluck('placement.company_id')
            ->unique()
            ->count();

        // Ungraded Submissions
        $ungradedSubmissions = Submission::where('status', SubmissionStatus::SUBMITTED->value)
            ->whereHas(
                'registration',
                fn ($q) => $q
                    ->where('status', 'active')
                    ->whereHas('mentors', fn ($q) => $q->where('user_id', $userId)),
            )
            ->count();

        // Supervision Logs count for the teacher
        $supervisionLogsCount = SupervisionLog::where('supervisor_id', $userId)->count();

        // Unresolved Incident Reports from supervised students
        $unresolvedIncidents = IncidentReport::whereIn('status', [
            IncidentStatus::REPORTED->value,
            IncidentStatus::INVESTIGATING->value,
        ])
            ->whereHas(
                'registration',
                fn ($q) => $q
                    ->where('status', 'active')
                    ->whereHas('mentors', fn ($q) => $q->where('user_id', $userId)),
            )
            ->count();

        return [
            'supervisedStudents' => $supervisedStudents,
            'pendingJournals' => $pendingJournals,
            'activeCompanies' => $activeCompanies,
            'ungradedSubmissions' => $ungradedSubmissions,
            'supervisionLogsCount' => $supervisionLogsCount,
            'unresolvedIncidents' => $unresolvedIncidents,
        ];
    }
}
