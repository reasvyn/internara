<?php

declare(strict_types=1);

namespace App\Domain\User\Actions;

use App\Domain\Core\Actions\BaseAction;
use App\Domain\Logbook\Models\Logbook;
use App\Domain\Registration\Models\Registration;
use Illuminate\Support\Facades\Auth;

class GetTeacherDashboardStatsAction extends BaseAction
{
    /** @return array{supervisedStudents: int, pendingJournals: int, activeCompanies: int} */
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

        return [
            'supervisedStudents' => $supervisedStudents,
            'pendingJournals' => $pendingJournals,
            'activeCompanies' => $activeCompanies,
        ];
    }
}
