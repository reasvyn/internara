<?php

declare(strict_types=1);

namespace App\Livewire\Assessment;

use App\Models\Assessment;
use App\Models\User;
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

    public function render()
    {
        return view('livewire.assessment.assessment-view');
    }
}
