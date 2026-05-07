<?php

declare(strict_types=1);

namespace App\Livewire\Mentor;

use App\Actions\Evaluation\EvaluateMentorAction;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class EvaluateMentor extends Component
{
    public User $mentor;

    public function mount(User $mentor): void
    {
        $this->mentor = $mentor;
    }

    public function evaluate(EvaluateMentorAction $action): void
    {
        Gate::authorize('evaluateMentor', $this->mentor);

        $action->execute(auth()->user(), $this->mentor, []);
        $this->dispatch('notify', type: 'success', message: 'Mentor evaluation submitted.');
    }

    public function render(): View
    {
        return view('livewire.mentor.evaluate-mentor', [
            'mentor' => $this->mentor,
        ]);
    }
}
