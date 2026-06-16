<?php

declare(strict_types=1);

namespace App\User\Dashboard\Livewire;

use App\User\Dashboard\Actions\ReadTeacherDashboardAction;
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
        $user = auth()->user();

        if (! $user) {
            abort(403);
        }

        if ($user->hasRole('teacher')) {
            return;
        }

        if ($user->hasRole('admin')) {
            return;
        }

        abort(403);
    }

    public function mount(ReadTeacherDashboardAction $action): void
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
