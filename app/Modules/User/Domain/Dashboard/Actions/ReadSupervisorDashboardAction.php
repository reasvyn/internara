<?php

declare(strict_types=1);

namespace App\Modules\User\Domain\Dashboard\Actions;

use App\Modules\Core\Actions\BaseReadAction;
use App\Modules\Enrollment\Domain\Registration\Models\Registration;
use App\Modules\Evaluation\Models\EvaluationResponse;
use App\Modules\Journals\Domain\Attendance\Models\Attendance;
use App\Modules\Journals\Domain\Logbook\Models\Logbook;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

final class ReadSupervisorDashboardAction extends BaseReadAction
{
    public function execute(): array
    {
        $userId = Auth::id();

        return Cache::remember(
            config('cache-keys.admin_dashboard_stats').'supervisor.'.$userId,
            300,
            fn () => $this->computeStats($userId),
        );
    }

    private function computeStats(string $userId): array
    {
        $activeInterns = Registration::where('status', 'active')
            ->whereHas('mentors', fn ($q) => $q->where('user_id', $userId))
            ->count();

        $pendingEvaluations = EvaluationResponse::where('target_type', 'mentor')
            ->where('target_id', $userId)
            ->count();

        $verifiedJournals = Logbook::where('is_verified', true)
            ->whereHas(
                'registration',
                fn ($q) => $q
                    ->where('status', 'active')
                    ->whereHas('mentors', fn ($q) => $q->where('user_id', $userId)),
            )
            ->count();

        $pendingJournals = Logbook::where('is_verified', false)
            ->whereHas(
                'registration',
                fn ($q) => $q
                    ->where('status', 'active')
                    ->whereHas('mentors', fn ($q) => $q->where('user_id', $userId)),
            )
            ->count();

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
