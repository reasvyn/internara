<?php

declare(strict_types=1);

namespace App\Domain\User\Actions;

use App\Domain\Assignment\Aggregates\Submission\Enums\SubmissionStatus;
use App\Domain\Assignment\Aggregates\Submission\Models\Submission;
use App\Domain\Core\Actions\BaseAction;
use App\Domain\Enrollment\Models\Registration;
use App\Domain\Guidance\Aggregates\SupervisionLog\Models\SupervisionLog;
use App\Domain\Incident\Aggregates\IncidentReport\Enums\IncidentStatus;
use App\Domain\Incident\Aggregates\IncidentReport\Models\IncidentReport;
use App\Domain\Journals\Aggregates\Logbook\Models\Logbook;
use Illuminate\Support\Facades\Auth;

final class GetTeacherDashboardStatsAction extends BaseAction
{
    /** @return array{supervisedStudents: int, pendingJournals: int, activeCompanies: int, ungradedSubmissions: int, supervisionLogsCount: int, unresolvedIncidents: int} */
    public function execute(): array
    {
        $userId = Auth::id();

        $supervisedStudents = Registration::whereHas('statuses', fn ($q) => $q->where('name', 'active'))
            ->whereHas('mentors', fn ($q) => $q->where('user_id', $userId))
            ->count();

        $pendingJournals = Logbook::where('status', 'submitted')
            ->whereHas('registration', fn ($q) => $q
                ->whereHas('statuses', fn ($q) => $q->where('name', 'active'))
                ->whereHas('mentors', fn ($q) => $q->where('user_id', $userId)))
            ->count();

        $activeCompanies = Registration::whereHas('statuses', fn ($q) => $q->where('name', 'active'))
            ->whereHas('mentors', fn ($q) => $q->where('user_id', $userId))
            ->whereHas('placement.company')
            ->get()
            ->pluck('placement.company_id')
            ->unique()
            ->count();

        // Ungraded Submissions
        $ungradedSubmissions = Submission::where('status', SubmissionStatus::SUBMITTED->value)
            ->whereHas('registration', fn ($q) => $q
                ->whereHas('statuses', fn ($q) => $q->where('name', 'active'))
                ->whereHas('mentors', fn ($q) => $q->where('user_id', $userId)))
            ->count();

        // Supervision Logs count for the teacher
        $supervisionLogsCount = SupervisionLog::where('supervisor_id', $userId)->count();

        // Unresolved Incident Reports from supervised students
        $unresolvedIncidents = IncidentReport::whereIn('status', [
            IncidentStatus::REPORTED->value,
            IncidentStatus::INVESTIGATING->value,
        ])
            ->whereHas('registration', fn ($q) => $q
                ->whereHas('statuses', fn ($q) => $q->where('name', 'active'))
                ->whereHas('mentors', fn ($q) => $q->where('user_id', $userId)))
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
