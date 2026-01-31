<?php

declare(strict_types=1);

namespace Modules\Mentor\Livewire;

use Livewire\Component;
use Modules\Assessment\Services\Contracts\AssessmentService;
use Modules\Internship\Services\Contracts\RegistrationService;

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

    public array $complianceMetrics = [];

    protected AssessmentService $assessmentService;

    /**
     * Inject dependencies in boot method.
     */
    public function boot(AssessmentService $assessmentService): void
    {
        $this->assessmentService = $assessmentService;
    }

    public function mount(string $registrationId)
    {
        $this->registrationId = $registrationId;

        // Authorization check for viewing
        $registration = app(RegistrationService::class)->find($registrationId);
        if (! $registration || $registration->mentor_id !== auth()->id()) {
            abort(403, 'You are not authorized to evaluate this intern.');
        }

        // Load compliance metrics
        $this->complianceMetrics = app(
            \Modules\Assessment\Services\Contracts\ComplianceService::class,
        )->calculateScore($registrationId);

        $assessment = $this->assessmentService->first([
            'registration_id' => $registrationId,
            'type' => 'mentor',
        ]);

        if ($assessment) {
            $this->criteria = $assessment->content ?? $this->criteria;
            $this->feedback = $assessment->feedback ?? '';
        }
    }

    public function save()
    {
        $this->validate([
            'criteria.*' => 'required|numeric|min:0|max:100',
            'feedback' => 'nullable|string|max:1000',
        ]);

        $this->assessmentService->submitEvaluation(
            $this->registrationId,
            auth()->id(),
            'mentor',
            $this->criteria,
            $this->feedback,
        );

        $this->dispatch('notify', message: __('assessment::messages.submitted'), type: 'success');
        $this->redirect(route('mentor.dashboard'), navigate: true);
    }

    public function render()
    {
        $registration = app(RegistrationService::class)->find($this->registrationId);
        $claimedCompetencies = app(
            \Modules\Assessment\Services\Contracts\CompetencyService::class,
        )->getClaimedCompetencies($this->registrationId);

        return view('mentor::livewire.evaluate-intern', [
            'registration' => $registration,
            'claimedCompetencies' => $claimedCompetencies,
        ])->layout('ui::components.layouts.dashboard', ['title' => 'Evaluate Intern']);
    }
}
