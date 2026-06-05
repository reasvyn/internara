<?php

declare(strict_types=1);

namespace App\User\Dashboard\Livewire;

use App\User\Dashboard\Actions\GetSupervisorDashboardStatsAction;
use Illuminate\View\View;

class SupervisorDashboard extends UserDashboard
{
    public int $activeInterns = 0;

    public int $pendingEvaluations = 0;

    public int $verifiedJournals = 0;

    public int $pendingJournals = 0;

    public int $pendingAttendance = 0;

    public function boot(): void
    {
        abort_unless(auth()->user()?->hasRole('supervisor'), 403);
    }

    public function mount(GetSupervisorDashboardStatsAction $action): void
    {
        $stats = $action->execute();

        $this->activeInterns = $stats['activeInterns'];
        $this->pendingEvaluations = $stats['pendingEvaluations'];
        $this->verifiedJournals = $stats['verifiedJournals'];
        $this->pendingJournals = $stats['pendingJournals'];
        $this->pendingAttendance = $stats['pendingAttendance'];
    }

    public function render(): View
    {
        return view('user.dashboard.supervisor');
    }
}
