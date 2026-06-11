<?php

declare(strict_types=1);

namespace App\Assessment\Livewire;

use App\Assessment\Actions\AutoCalculateAssessmentAction;
use App\Assessment\Actions\FinalizeAssessmentAction;
use App\Assessment\Actions\InitializeAssessmentAction;
use App\Assessment\Actions\UpdateAssessmentScoresAction;
use App\Assessment\Models\Assessment;
use App\Assessment\Rubric\Models\Competency;
use App\Enrollment\Registration\Models\Registration;
use App\Guidance\Mentor\Models\Mentor;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

class AssessmentGrading extends Component
{
    public string $registrationId = '';

    public ?string $assessmentId = null;

    public array $scores = [];

    public bool $isFinalized = false;

    public function mount(string $registrationId, InitializeAssessmentAction $action): void
    {
        $this->registrationId = $registrationId;

        $result = $action->execute($registrationId);
        $assessment = $result['assessment'];

        if ($assessment === null) {
            return;
        }

        $this->assessmentId = $assessment->id;
        $this->isFinalized = $assessment->finalized_at !== null;

        $content = $assessment->content ?? [];
        $competencies = $content['competencies'] ?? [];
        foreach ($competencies as $compId => $compData) {
            foreach ($compData['indicators'] ?? [] as $indId => $score) {
                $this->scores["{$compId}.{$indId}"] = (string) $score;
            }
        }
    }

    #[Computed]
    public function registration(): Registration
    {
        return Registration::with(['mentee.user', 'internship'])->findOrFail($this->registrationId);
    }

    #[Computed]
    public function assessment(): ?Assessment
    {
        if ($this->assessmentId === null) {
            return null;
        }

        return Assessment::with('rubric.competencies.indicators')->find($this->assessmentId);
    }

    #[Computed]
    public function evaluableCompetencies(): Collection
    {
        $assessment = $this->assessment;
        if ($assessment === null || $assessment->rubric === null) {
            return new Collection;
        }

        $user = auth()->user();

        return $assessment->rubric->competencies
            ->filter(function (Competency $competency) use ($user) {
                if ($competency->evaluator_role->value === 'system') {
                    return false;
                }

                if ($user->hasRole('super_admin') || $user->hasRole('admin')) {
                    return true;
                }

                if (! $user->hasRole($competency->evaluator_role->value)) {
                    return false;
                }

                return $this->isAssignedAsMentor($competency->evaluator_role->value);
            })
            ->values();
    }

    private function isAssignedAsMentor(string $evaluatorRole): bool
    {
        $type =
            $evaluatorRole === 'teacher'
                ? Mentor::TYPE_SCHOOL_TEACHER
                : Mentor::TYPE_INDUSTRY_SUPERVISOR;

        return Mentor::where('user_id', auth()->id())
            ->where('type', $type)
            ->whereHas(
                'registrations',
                fn ($q) => $q->where('registration_id', $this->registrationId),
            )
            ->exists();
    }

    #[Computed]
    public function readOnlyCompetencies(): Collection
    {
        $assessment = $this->assessment;
        if ($assessment === null || $assessment->rubric === null) {
            return new Collection;
        }

        $user = auth()->user();

        return $assessment->rubric->competencies
            ->filter(function (Competency $competency) use ($user) {
                if ($competency->evaluator_role->value === 'system') {
                    return true;
                }

                return ! $user->hasRole($competency->evaluator_role->value) &&
                    ! $user->hasRole('super_admin') &&
                    ! $user->hasRole('admin');
            })
            ->values();
    }

    public function updatedScores($value, string $key, UpdateAssessmentScoresAction $action): void
    {
        if ($this->isFinalized) {
            return;
        }

        $parts = explode('.', $key);
        if (count($parts) !== 2) {
            return;
        }

        [$competencyId, $indicatorId] = $parts;

        $assessment = $this->assessment;
        if ($assessment === null) {
            return;
        }

        $score = is_numeric($value) ? (float) $value : null;

        $action->execute($assessment, $competencyId, $indicatorId, $score);
    }

    public function autoImport(AutoCalculateAssessmentAction $action): void
    {
        $assessment = $this->assessment;
        if ($assessment === null || $this->isFinalized) {
            return;
        }

        $action->execute($assessment);
        flash()->success('Submission & logbook scores imported.');
    }

    public function finalize(FinalizeAssessmentAction $action): void
    {
        $assessment = $this->assessment;
        if ($assessment === null || $this->isFinalized) {
            return;
        }

        try {
            $action->execute($assessment, auth()->user());
            $this->isFinalized = true;
            flash()->success('Assessment finalized.');
        } catch (\Throwable $e) {
            flash()->error($e->getMessage());
        }
    }

    public function render(): View
    {
        return view('assessment.assessment-grading');
    }
}
