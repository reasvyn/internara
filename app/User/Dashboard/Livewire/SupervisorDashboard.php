<?php

declare(strict_types=1);

namespace App\User\Dashboard\Livewire;

use App\User\Dashboard\Actions\ReadSupervisorDashboardAction;
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
        $user = auth()->user();

        if (! $user) {
            abort(403);
        }

        if ($user->hasRole('supervisor')) {
            return;
        }

        if ($user->hasRole('admin')) {
            return;
        }

        abort(403);
    }

    public function mount(ReadSupervisorDashboardAction $action): void
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
