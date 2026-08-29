<?php

declare(strict_types=1);

namespace App\Modules\Assessment\Livewire;

use App\Modules\Assessment\Actions\AutoCalculateAssessmentAction;
use App\Modules\Assessment\Actions\FinalizeAssessmentAction;
use App\Modules\Assessment\Actions\InitializeAssessmentAction;
use App\Modules\Assessment\Actions\UpdateAssessmentScoresAction;
use App\Modules\Assessment\Data\UpdateAssessmentScoresData;
use App\Modules\Assessment\Models\Assessment;
use App\Modules\Core\Livewire\BaseFormView;
use App\Modules\Enrollment\Domain\Registration\Models\Registration;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use TallStackUi\Traits\Interactions;

class AssessmentGrading extends BaseFormView
{
    use Interactions;

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

        $content = $assessment->scores_data ?? [];
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
        return Registration::with(['student', 'internship', 'mentee.user'])->findOrFail($this->registrationId);
    }

    #[Computed]
    public function assessment(): ?Assessment
    {
        if ($this->assessmentId === null) {
            return null;
        }

        return Assessment::with('rubric')->find($this->assessmentId);
    }

    #[Computed]
    public function evaluableCompetencies(): Collection
    {
        $assessment = $this->assessment;
        if ($assessment === null || $assessment->rubric === null) {
            return new Collection;
        }

        $user = auth()->user();

        $competencies = $assessment->rubric->structure['competencies'] ?? [];

        return collect($competencies)
            ->filter(function (array $competency) use ($user) {
                $role = $competency['evaluator_role'] ?? 'teacher';

                if ($role === 'system') {
                    return false;
                }

                if ($user->hasRole('super_admin') || $user->hasRole('admin')) {
                    return true;
                }

                if (! $user->hasRole($role)) {
                    return false;
                }

                return $this->isAssignedAsMentor($role);
            })
            ->values();
    }

    private function isAssignedAsMentor(string $evaluatorRole): bool
    {
        $user = auth()->user();

        if ($user === null) {
            return false;
        }

        $entity = $this->registration->asMentorEntity();

        return match ($evaluatorRole) {
            'teacher' => $entity->canGradeSubmission($user) || $entity->canVerifyAttendance($user),
            'supervisor' => $entity->canVerifyLogbook($user) || $entity->canReviewSupervisionLog($user),
            default => $entity->isMentor($user),
        };
    }

    #[Computed]
    public function readOnlyCompetencies(): Collection
    {
        $assessment = $this->assessment;
        if ($assessment === null || $assessment->rubric === null) {
            return new Collection;
        }

        $user = auth()->user();

        $competencies = $assessment->rubric->structure['competencies'] ?? [];

        return collect($competencies)
            ->filter(function (array $competency) use ($user) {
                $role = $competency['evaluator_role'] ?? 'teacher';

                if ($role === 'system') {
                    return true;
                }

                return ! $user->hasRole($role) &&
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

        $action->execute($assessment, new UpdateAssessmentScoresData(
            competencyId: $competencyId,
            indicatorId: $indicatorId,
            score: $score,
        ));
    }

    public function autoImport(AutoCalculateAssessmentAction $action): void
    {
        $assessment = $this->assessment;
        if ($assessment === null || $this->isFinalized) {
            return;
        }

        $action->execute($assessment);
        $this->toast()->success(__('assessment.auto_scores_imported'))->send();
    }

    public function askFinalize(): void
    {
        $this->dialog()
            ->question(__('common.actions.confirm_action'), $this->confirmMessage ?? __('common.actions.confirm_message'))
            ->confirm(text: __('common.actions.confirm'), method: 'confirmAction')
            ->cancel(text: __('common.actions.cancel'))
            ->send();
    }

    public function confirmAction(FinalizeAssessmentAction $action): void
    {
        $assessment = $this->assessment;
        if ($assessment === null || $this->isFinalized) {
            return;
        }

        $this->handleSave(function () use ($action, $assessment) {
            $action->execute($assessment, auth()->user());
            $this->isFinalized = true;
            $this->toast()->success(__('assessment.finalized_success'))->send();
        });
    }

    public function render(): View
    {
        return view('assessment.assessment-grading');
    }
}
