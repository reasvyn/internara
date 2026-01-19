<?php

declare(strict_types=1);

namespace Modules\Mentor\Livewire;

use Livewire\Component;
use Modules\Assessment\Services\Contracts\AssessmentService;
use Modules\Internship\Services\Contracts\InternshipRegistrationService;

class EvaluateIntern extends Component
{
    public string $registrationId;
    public array $criteria = [
        'work_quality' => 0,
        'initiative' => 0,
        'punctuality' => 0,
        'communication' => 0,
    ];
    public string $feedback = '';

    public function mount(string $registrationId)
    {
        $this->registrationId = $registrationId;
        
        $assessment = app(AssessmentService::class)->first([
            'registration_id' => $registrationId,
            'type' => 'mentor',
        ]);

        if ($assessment) {
            $this->criteria = $assessment->content ?? $this->criteria;
            $this->feedback = $assessment->feedback ?? '';
        }
    }

    public function save(AssessmentService $assessmentService)
    {
        $this->validate([
            'criteria.*' => 'required|numeric|min:0|max:100',
            'feedback' => 'nullable|string|max:1000',
        ]);

        $assessmentService->submitEvaluation(
            $this->registrationId,
            auth()->id(),
            'mentor',
            $this->criteria,
            $this->feedback
        );

        $this->dispatch('notify', message: 'Evaluation submitted successfully!', type: 'success');
        $this->redirect(route('mentor.dashboard'), navigate: true);
    }

    public function render()
    {
        $registration = app(InternshipRegistrationService::class)->find($this->registrationId);

        return view('mentor::livewire.evaluate-intern', [
            'registration' => $registration
        ])->layout('ui::components.layouts.dashboard', ['title' => 'Evaluate Intern']);
    }
}
