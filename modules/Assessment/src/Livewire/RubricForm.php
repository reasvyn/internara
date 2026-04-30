<?php

declare(strict_types=1);

namespace Modules\Assessment\Livewire;

use Livewire\Component;
use Modules\Assessment\Services\Contracts\AssessmentService;
use Modules\Assessment\Services\Contracts\CompetencyService;
use Modules\Internship\Services\Contracts\RegistrationService;

class RubricForm extends Component
{
    public string $registrationId;

    public string $type = 'mentor'; // Default to mentor, can be 'teacher'

    public array $scores = [];

    public ?string $feedback = null;

    public array $competencies = [];

    public function mount(
        string $registrationId,
        CompetencyService $competencyService,
        RegistrationService $registrationService,
        string $type = 'mentor',
    ): void {
        $this->registrationId = $registrationId;
        $this->type = $type;

        $registration = $registrationService->find($registrationId);
        if (
            $registration &&
            $registration->internship &&
            $registration->internship->department_id
        ) {
            $this->competencies = $competencyService
                ->getForDepartment($registration->internship->department_id)
                ->toArray();

            // Initialize scores
            foreach ($this->competencies as $competency) {
                $this->scores[$competency['id']] = 0;
            }
        }
    }

    public function submit(AssessmentService $service): void
    {
        $this->validate([
            'scores.*' => 'required|numeric|min:0|max:100',
            'feedback' => 'nullable|string|max:1000',
        ]);

        try {
            $service->submitEvaluation(
                $this->registrationId,
                (string) auth()->id(),
                $this->type,
                $this->scores,
                $this->feedback,
            );

            flash()->success(__('assessment::messages.submitted'));

            $this->dispatch('evaluation-submitted');
        } catch (\Exception $e) {
            flash()->error($e->getMessage());
        }
    }

    public function render()
    {
        return view('assessment::livewire.rubric-form');
    }
}
