<?php

declare(strict_types=1);

namespace Modules\Teacher\Livewire;

use Livewire\Component;
use Modules\Assessment\Services\Contracts\AssessmentService;
use Modules\Internship\Services\Contracts\InternshipRegistrationService;

class AssessInternship extends Component
{
    public string $registrationId;
    public array $criteria = [
        'discipline' => 0,
        'teamwork' => 0,
        'technical_skill' => 0,
        'attitude' => 0,
    ];
    public string $feedback = '';

    public function mount(string $registrationId)
    {
        $this->registrationId = $registrationId;
        
        // Load existing assessment if any
        $assessment = app(AssessmentService::class)->first([
            'registration_id' => $registrationId,
            'type' => 'teacher',
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
            'teacher',
            $this->criteria,
            $this->feedback
        );

        $this->dispatch('notify', message: 'Evaluation submitted successfully!', type: 'success');
        $this->redirect(route('teacher.dashboard'), navigate: true);
    }

    public function render()
    {
        $registration = app(InternshipRegistrationService::class)->find($this->registrationId);

        return view('teacher::livewire.assess-internship', [
            'registration' => $registration
        ])->layout('ui::components.layouts.dashboard', ['title' => 'Assess Student']);
    }
}
