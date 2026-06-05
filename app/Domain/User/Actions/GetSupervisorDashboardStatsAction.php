<?php

declare(strict_types=1);

namespace App\Domain\User\Actions;

use App\Domain\Core\Actions\BaseAction;
use App\Domain\Enrollment\Models\Registration;
use App\Domain\Evaluation\Aggregates\Evaluation\Models\Evaluation;
use App\Domain\Journals\Aggregates\Attendance\Models\Attendance;
use App\Domain\Journals\Aggregates\Logbook\Models\Logbook;
use Illuminate\Support\Facades\Auth;

final class GetSupervisorDashboardStatsAction extends BaseAction
{
    /** @return array{activeInterns: int, pendingEvaluations: int, verifiedJournals: int, pendingJournals: int, pendingAttendance: int} */
    public function execute(): array
    {
        $userId = Auth::id();

        $activeInterns = Registration::whereHas('statuses', fn ($q) => $q->where('name', 'active'))
            ->whereHas('mentors', fn ($q) => $q->where('user_id', $userId))
            ->count();

        $pendingEvaluations = Evaluation::where('mentor_id', $userId)->count();

        $verifiedJournals = Logbook::where('is_verified', true)
            ->whereHas('registration', fn ($q) => $q
                ->whereHas('statuses', fn ($q) => $q->where('name', 'active'))
                ->whereHas('mentors', fn ($q) => $q->where('user_id', $userId)))
            ->count();

        // Pending (Unverified) Journals of active interns
        $pendingJournals = Logbook::where('is_verified', false)
            ->whereHas('registration', fn ($q) => $q
                ->whereHas('statuses', fn ($q) => $q->where('name', 'active'))
                ->whereHas('mentors', fn ($q) => $q->where('user_id', $userId)))
            ->count();

        // Pending (Unverified) Attendance approvals of active interns
        $pendingAttendance = Attendance::where('is_verified', false)
            ->whereHas('registration', fn ($q) => $q
                ->whereHas('statuses', fn ($q) => $q->where('name', 'active'))
                ->whereHas('mentors', fn ($q) => $q->where('user_id', $userId)))
            ->count();

        return [
            'activeInterns' => $activeInterns,
            'pendingEvaluations' => $pendingEvaluations,
            'verifiedJournals' => $verifiedJournals,
            'pendingJournals' => $pendingJournals,
            'pendingAttendance' => $pendingAttendance,
        ];
    }
}
