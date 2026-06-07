<?php

declare(strict_types=1);

namespace App\User\Dashboard\Actions;

use App\Core\Actions\BaseAction;
use App\Enrollment\Models\Registration;
use App\Evaluation\Models\Evaluation;
use App\Journals\Attendance\Models\Attendance;
use App\Journals\Logbook\Models\Logbook;
use Illuminate\Support\Facades\Auth;

final class GetSupervisorDashboardStatsAction extends BaseAction
{
    /** @return array{activeInterns: int, pendingEvaluations: int, verifiedJournals: int, pendingJournals: int, pendingAttendance: int} */
    public function execute(): array
    {
        $userId = Auth::id();

        $activeInterns = Registration::where('status', 'active')
            ->whereHas('mentors', fn ($q) => $q->where('user_id', $userId))
            ->count();

        $pendingEvaluations = Evaluation::where('mentor_id', $userId)->count();

        $verifiedJournals = Logbook::where('is_verified', true)
            ->whereHas(
                'registration',
                fn ($q) => $q
                    ->where('status', 'active')
                    ->whereHas('mentors', fn ($q) => $q->where('user_id', $userId)),
            )
            ->count();

        // Pending (Unverified) Journals of active interns
        $pendingJournals = Logbook::where('is_verified', false)
            ->whereHas(
                'registration',
                fn ($q) => $q
                    ->where('status', 'active')
                    ->whereHas('mentors', fn ($q) => $q->where('user_id', $userId)),
            )
            ->count();

        // Pending (Unverified) Attendance approvals of active interns
        $pendingAttendance = Attendance::where('is_verified', false)
            ->whereHas(
                'registration',
                fn ($q) => $q
                    ->where('status', 'active')
                    ->whereHas('mentors', fn ($q) => $q->where('user_id', $userId)),
            )
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
