<?php

declare(strict_types=1);

namespace Modules\Teacher\Livewire;

use Livewire\Component;
use Modules\Assessment\Services\Contracts\AssessmentService;
use Modules\Internship\Services\Contracts\RegistrationService;

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
        if (! $registration || $registration->teacher_id !== auth()->id()) {
            abort(403, 'You are not authorized to assess this student.');
        }

        // Load compliance metrics
        $this->complianceMetrics = app(
            \Modules\Assessment\Services\Contracts\ComplianceService::class,
        )->calculateScore($registrationId);

        // Load existing assessment if any
        $assessment = $this->assessmentService->first([
            'registration_id' => $registrationId,
            'type' => 'teacher',
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
            'teacher',
            $this->criteria,
            $this->feedback,
        );

        flash()->success(__('assessment::messages.submitted'));
        $this->redirect(route('teacher.dashboard'), navigate: true);
    }

    public function render()
    {
        $registration = app(RegistrationService::class)->find($this->registrationId);
        $claimedCompetencies = app(
            \Modules\Assessment\Services\Contracts\CompetencyService::class,
        )->getClaimedCompetencies($this->registrationId);

        return view('teacher::livewire.assess-internship', [
            'registration' => $registration,
            'claimedCompetencies' => $claimedCompetencies,
        ])->layout('ui::components.layouts.dashboard', ['title' => 'Assess Student']);
    }
}
