<?php

declare(strict_types=1);

namespace App\Guidance\Mentor\Livewire;

use App\Evaluation\Evaluation\Actions\EvaluateMentorAction;
use App\User\Models\User;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class EvaluateMentor extends Component
{
    public string $mentorId = '';

    public string $feedback = '';

    public int $scoreCommunication = 0;

    public int $scoreResponsiveness = 0;

    public int $scoreGuidance = 0;

    public function submit(EvaluateMentorAction $action): void
    {
        $this->validate([
            'mentorId' => 'required|exists:users,id',
            'feedback' => 'required|string|max:2000',
            'scoreCommunication' => 'required|integer|min:0|max:100',
            'scoreResponsiveness' => 'required|integer|min:0|max:100',
            'scoreGuidance' => 'required|integer|min:0|max:100',
        ]);

        $mentor = User::findOrFail($this->mentorId);

        $action->execute(auth()->user(), $mentor, [
            'feedback' => $this->feedback,
            'criteria_scores' => [
                'communication' => $this->scoreCommunication,
                'responsiveness' => $this->scoreResponsiveness,
                'guidance_quality' => $this->scoreGuidance,
            ],
            'overall_score' => (int) round(
                ($this->scoreCommunication + $this->scoreResponsiveness + $this->scoreGuidance) / 3,
            ),
        ]);

        $this->reset();
        flash()->success('Mentor evaluation submitted.');
    }

    public function render(): View
    {
        $mentors = User::role('supervisor')->orderBy('name')->get(['id', 'name']);

        return view('guidance.mentor.evaluate-mentor', [
            'mentors' => $mentors,
        ]);
    }
}
