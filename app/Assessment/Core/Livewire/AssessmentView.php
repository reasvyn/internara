<?php

declare(strict_types=1);

namespace App\Assessment\Core\Livewire;

use App\Assessment\Core\Models\Assessment;
use App\User\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

class AssessmentView extends Component
{
    #[Computed]
    public function assessments(): Collection
    {
        /** @var User $user */
        $user = auth()->user();

        return Assessment::with(['registration.mentee.user', 'rubric.competencies.indicators', 'registration.internship'])
            ->whereHas('registration.mentee.user', fn ($q) => $q->where('id', $user->id))
            ->whereNotNull('finalized_at')
            ->latest()
            ->get();
    }

    public function render(): View
    {
        return view('assessment.core.assessment-view');
    }
}
