<?php

declare(strict_types=1);

namespace App\Modules\User\Domain\Dashboard\Actions;

use App\Modules\Assignment\Domain\Submission\Enums\SubmissionStatus;
use App\Modules\Assignment\Domain\Submission\Models\Submission;
use App\Modules\Core\Actions\BaseReadAction;
use App\Modules\Enrollment\Domain\Registration\Models\Registration;
use App\Modules\Journal\Domain\Logbook\Models\Logbook;
use App\Modules\Journal\Domain\SupervisionLog\Models\SupervisionLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

final class ReadTeacherDashboardAction extends BaseReadAction
{
    public function execute(): array
    {
        $userId = Auth::id();

        return Cache::remember(
            config('cache-keys.admin_dashboard_stats').'teacher.'.$userId,
            300,
            fn () => $this->computeStats($userId),
        );
    }

    private function computeStats(string $userId): array
    {
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

        $activeCompanies = Registration::where('registrations.status', 'active')
            ->whereHas('mentors', fn ($q) => $q->where('user_id', $userId))
            ->join('placements', 'registrations.placement_id', '=', 'placements.id')
            ->whereNotNull('placements.company_id')
            ->distinct('placements.company_id')
            ->count('placements.company_id');

        $ungradedSubmissions = Submission::where('status', SubmissionStatus::SUBMITTED->value)
            ->whereHas(
                'registration',
                fn ($q) => $q
                    ->where('status', 'active')
                    ->whereHas('mentors', fn ($q) => $q->where('user_id', $userId)),
            )
            ->count();

        $supervisionLogsCount = SupervisionLog::where('supervisor_id', $userId)->count();

        return [
            'supervisedStudents' => $supervisedStudents,
            'pendingJournals' => $pendingJournals,
            'activeCompanies' => $activeCompanies,
            'ungradedSubmissions' => $ungradedSubmissions,
            'supervisionLogsCount' => $supervisionLogsCount,
        ];
    }
}
