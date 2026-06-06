<?php

declare(strict_types=1);

namespace App\Evaluation\Livewire;

use App\Evaluation\Actions\DeleteEvaluationAction;
use App\Evaluation\Actions\SubmitEvaluationAction;
use App\Evaluation\Enums\EvaluationCategory;
use App\Evaluation\Models\Evaluation;
use App\User\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

class MentorEvaluationManager extends Component
{
    use WithPagination;

    public bool $showForm = false;

    public ?Evaluation $editingEvaluation = null;

    public string $evaluationType = 'mentor';

    public string $mentorId = '';

    public ?float $overallScore = null;

    public ?string $feedback = '';

    public array $criteriaScores = [];

    public string $filterType = '';

    protected function queryString(): array
    {
        return ['filterType' => ['as' => 'type']];
    }

    public function mount(): void
    {
        $this->resetCriteria();
    }

    public function updatedEvaluationType(): void
    {
        $this->resetCriteria();
    }

    public function create(): void
    {
        $this->resetForm();
        $this->evaluationType = 'mentor';
        $this->showForm = true;
    }

    public function store(SubmitEvaluationAction $action): void
    {
        $rules = [
            'evaluationType' => 'required|in:'.collect(EvaluationCategory::cases())->pluck('value')->implode(','),
            'overallScore' => 'required|numeric|min:0|max:100',
            'feedback' => 'nullable|string|max:2000',
            'criteriaScores.*' => 'nullable|numeric|min:0|max:100',
        ];

        if ($this->evaluationType === 'mentor') {
            $rules['mentorId'] = 'required|exists:users,id';
        }

        $this->validate($rules);

        $data = [
            'overall_score' => $this->overallScore,
            'feedback' => $this->feedback,
            'criteria_scores' => array_filter($this->criteriaScores, fn ($v) => is_numeric($v) && $v > 0),
        ];

        if ($this->evaluationType === 'mentor') {
            $data['mentor_id'] = $this->mentorId;
        }

        $type = EvaluationCategory::from($this->evaluationType);

        $action->execute(Auth::user(), $type, $data);

        $this->showForm = false;
        $this->resetForm();
        flash()->success(__('evaluation.submit_success'));
    }

    public function edit(Evaluation $evaluation): void
    {
        $this->editingEvaluation = $evaluation;
        $this->evaluationType = $evaluation->evaluation_type->value;
        $this->mentorId = $evaluation->mentor_id ?? '';
        $this->overallScore = $evaluation->overall_score;
        $this->feedback = $evaluation->feedback;
        $this->resetCriteria();
        $this->criteriaScores = array_merge(
            $this->criteriaScores,
            $evaluation->criteria_scores ?? [],
        );
        $this->showForm = true;
    }

    public function update(SubmitEvaluationAction $action): void
    {
        if (! $this->editingEvaluation) {
            return;
        }

        $rules = [
            'evaluationType' => 'required|in:'.collect(EvaluationCategory::cases())->pluck('value')->implode(','),
            'overallScore' => 'required|numeric|min:0|max:100',
            'feedback' => 'nullable|string|max:2000',
            'criteriaScores.*' => 'nullable|numeric|min:0|max:100',
        ];

        if ($this->evaluationType === 'mentor') {
            $rules['mentorId'] = 'required|exists:users,id';
        }

        $this->validate($rules);

        $data = [
            'overall_score' => $this->overallScore,
            'feedback' => $this->feedback,
            'criteria_scores' => array_filter($this->criteriaScores, fn ($v) => is_numeric($v) && $v > 0),
        ];

        if ($this->evaluationType === 'mentor') {
            $data['mentor_id'] = $this->mentorId;
        }

        $type = EvaluationCategory::from($this->evaluationType);

        $action->execute(Auth::user(), $type, $data, $this->editingEvaluation);

        $this->showForm = false;
        $this->resetForm();
        flash()->success(__('evaluation.update_success'));
    }

    public function delete(Evaluation $evaluation, DeleteEvaluationAction $action): void
    {
        $action->execute($evaluation);
        flash()->success(__('evaluation.delete_success'));
    }

    public function cancel(): void
    {
        $this->showForm = false;
        $this->resetForm();
    }

    private function resetCriteria(): void
    {
        $type = EvaluationCategory::tryFrom($this->evaluationType) ?? EvaluationCategory::MENTOR;
        $this->criteriaScores = array_map(fn () => 0, $type->defaultCriteria());
    }

    private function resetForm(): void
    {
        $this->evaluationType = 'mentor';
        $this->mentorId = '';
        $this->overallScore = null;
        $this->feedback = '';
        $this->resetCriteria();
        $this->editingEvaluation = null;
    }

    #[Computed]
    public function typeOptions(): array
    {
        return collect(EvaluationCategory::cases())
            ->map(fn ($t) => ['id' => $t->value, 'name' => $t->label()])
            ->toArray();
    }

    #[Computed]
    public function criteriaLabels(): array
    {
        $type = EvaluationCategory::tryFrom($this->evaluationType) ?? EvaluationCategory::MENTOR;

        return $type->defaultCriteria();
    }

    #[Layout('core::layouts.app')]
    public function render(): View
    {
        $query = Evaluation::query()->with(['evaluator', 'mentor']);

        if ($this->filterType) {
            $type = EvaluationCategory::tryFrom($this->filterType);
            if ($type) {
                $query->ofType($type);
            }
        }

        $evaluations = $query->latest()->paginate(10);

        $mentors = User::role('supervisor')->orderBy('name')->get();

        return view('evaluation.core.mentor-evaluation-manager', [
            'evaluations' => $evaluations,
            'mentors' => $mentors,
        ]);
    }
}
