<?php

declare(strict_types=1);

namespace App\Domain\User\Livewire\Dashboards;

use App\Domain\Logbook\Models\Logbook;
use App\Domain\Registration\Models\Registration;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

class TeacherDashboard extends Component
{
    public function boot(): void
    {
        abort_unless(auth()->user()->hasRole('teacher'), 403);
    }

    #[Computed]
    public function supervisedStudents(): int
    {
        return Registration::whereHas('statuses', fn ($q) => $q->where('name', 'active'))
            ->whereHas('mentors', fn ($q) => $q->where('user_id', Auth::id()))
            ->count();
    }

    #[Computed]
    public function pendingJournals(): int
    {
        return Logbook::where('status', 'submitted')
            ->whereHas('registration', fn ($q) => $q
                ->whereHas('statuses', fn ($q) => $q->where('name', 'active'))
                ->whereHas('mentors', fn ($q) => $q->where('user_id', Auth::id())))
            ->count();
    }

    #[Computed]
    public function activeCompanies(): int
    {
        return Registration::whereHas('statuses', fn ($q) => $q->where('name', 'active'))
            ->whereHas('mentors', fn ($q) => $q->where('user_id', Auth::id()))
            ->whereHas('placement.company')
            ->get()
            ->pluck('placement.company_id')
            ->unique()
            ->count();
    }

    #[Layout('shared::layouts.app')]
    public function render(): View
    {
        return view('user.dashboards.teacher');
    }
}
