<?php

declare(strict_types=1);

namespace App\Livewire\Assessment;

use App\Actions\Assessment\AutoCalculateAssessmentAction;
use App\Actions\Assessment\FinalizeAssessmentAction;
use App\Models\Assessment;
use App\Models\Competency;
use App\Models\Mentor;
use App\Models\Registration;
use App\Models\Rubric;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Mary\Traits\Toast;

class AssessmentGrading extends Component
{
    use Toast;

    public string $registrationId = '';

    public ?string $assessmentId = null;

    public array $scores = [];

    public bool $isFinalized = false;

    public function mount(string $registrationId): void
    {
        $this->registrationId = $registrationId;

        $registration = Registration::with('internship')->findOrFail($registrationId);

        $rubric = Rubric::where('internship_id', $registration->internship_id)
            ->orWhereNull('internship_id')
            ->where('is_active', true)
            ->first();

        if ($rubric === null) {
            return;
        }

        $assessment = Assessment::firstOrCreate(
            ['registration_id' => $registrationId],
            [
                'rubric_id' => $rubric->id,
                'type' => 'final',
            ],
        );

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
        $type = $evaluatorRole === 'teacher'
            ? Mentor::TYPE_SCHOOL_TEACHER
            : Mentor::TYPE_INDUSTRY_SUPERVISOR;

        return Mentor::where('user_id', auth()->id())
            ->where('type', $type)
            ->whereHas('registrations', fn ($q) => $q->where('registration_id', $this->registrationId))
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

                return ! $user->hasRole($competency->evaluator_role->value)
                    && ! $user->hasRole('super_admin')
                    && ! $user->hasRole('admin');
            })
            ->values();
    }

    public function updatedScores($value, string $key): void
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

        if ($score === null || $score < 0) {
            return;
        }

        $content = $assessment->content ?? [];
        $content['competencies'][$competencyId]['evaluator_id'] = auth()->id();
        $content['competencies'][$competencyId]['evaluated_at'] = now()->toIso8601String();

        if ($score === null) {
            unset($content['competencies'][$competencyId]['indicators'][$indicatorId]);
        } else {
            $content['competencies'][$competencyId]['indicators'][$indicatorId] = $score;
        }

        $assessment->update(['content' => $content]);
    }

    public function autoImport(AutoCalculateAssessmentAction $action): void
    {
        $assessment = $this->assessment;
        if ($assessment === null || $this->isFinalized) {
            return;
        }

        $action->execute($assessment);
        $this->success('Submission & logbook scores imported.');
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
            $this->success('Assessment finalized.');
        } catch (\RuntimeException $e) {
            $this->error($e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.assessment.assessment-grading');
    }
}
