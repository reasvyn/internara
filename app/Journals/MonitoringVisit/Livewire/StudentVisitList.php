<?php

declare(strict_types=1);

namespace App\Journals\MonitoringVisit\Livewire;

use App\Journals\MonitoringVisit\Models\MonitoringVisit;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

class StudentVisitList extends Component
{
    #[Computed]
    public function visits(): Collection
    {
        $user = auth()->user();
        $registration = $user->registrations()->where('status', 'active')->first();

        if (! $registration) {
            return collect();
        }

        return MonitoringVisit::where('registration_id', $registration->id)
            ->with('teacher')
            ->latest('visit_date')
            ->get();
    }

    #[Layout('core::layouts.app')]
    public function render(): View
    {
        return view('journals.monitoring-visit.student-visit-list');
    }
}
