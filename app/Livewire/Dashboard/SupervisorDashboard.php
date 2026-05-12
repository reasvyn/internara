<?php

declare(strict_types=1);

namespace App\Livewire\Dashboard;

use App\Models\Evaluation;
use App\Models\Logbook;
use App\Models\Registration;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

class SupervisorDashboard extends Component
{
    public function boot(): void
    {
        abort_unless(auth()->user()->hasRole('supervisor'), 403);
    }

    #[Computed]
    public function activeInterns(): int
    {
        return Registration::whereHas('statuses', fn ($q) => $q->where('name', 'active'))
            ->whereHas('mentors', fn ($q) => $q->where('user_id', Auth::id()))
            ->count();
    }

    #[Computed]
    public function pendingEvaluations(): int
    {
        return Evaluation::where('mentor_id', Auth::id())->count();
    }

    #[Computed]
    public function verifiedJournals(): int
    {
        return Logbook::where('is_verified', true)
            ->whereHas('registration', fn ($q) => $q
                ->whereHas('statuses', fn ($q) => $q->where('name', 'active'))
                ->whereHas('mentors', fn ($q) => $q->where('user_id', Auth::id())))
            ->count();
    }

    #[Layout('layouts::app')]
    public function render()
    {
        return view('livewire.dashboard.supervisor');
    }
}
