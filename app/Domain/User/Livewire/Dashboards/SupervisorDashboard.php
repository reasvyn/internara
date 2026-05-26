<?php

declare(strict_types=1);

namespace App\Domain\User\Livewire\Dashboards;

use App\Domain\User\Actions\GetSupervisorDashboardStatsAction;
use App\Domain\User\Livewire\UserDashboard;
use Illuminate\View\View;

class SupervisorDashboard extends UserDashboard
{
    public int $activeInterns = 0;

    public int $pendingEvaluations = 0;

    public int $verifiedJournals = 0;

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
    }

    public function render(): View
    {
        return view('user.dashboards.supervisor');
    }
}
