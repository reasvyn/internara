<?php

declare(strict_types=1);

namespace App\Domain\User\Aggregates\Dashboard\Livewire;

use App\Domain\User\Aggregates\Dashboard\Actions\GetTeacherDashboardStatsAction;
use Illuminate\View\View;

class TeacherDashboard extends UserDashboard
{
    public int $supervisedStudents = 0;

    public int $pendingJournals = 0;

    public int $activeCompanies = 0;

    public int $ungradedSubmissions = 0;

    public int $supervisionLogsCount = 0;

    public int $unresolvedIncidents = 0;

    public function boot(): void
    {
        abort_unless(auth()->user()?->hasRole('teacher'), 403);
    }

    public function mount(GetTeacherDashboardStatsAction $action): void
    {
        $stats = $action->execute();

        $this->supervisedStudents = $stats['supervisedStudents'];
        $this->pendingJournals = $stats['pendingJournals'];
        $this->activeCompanies = $stats['activeCompanies'];
        $this->ungradedSubmissions = $stats['ungradedSubmissions'];
        $this->supervisionLogsCount = $stats['supervisionLogsCount'];
        $this->unresolvedIncidents = $stats['unresolvedIncidents'];
    }

    public function render(): View
    {
        return view('user.dashboard.teacher');
    }
}
