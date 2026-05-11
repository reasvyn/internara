<?php

declare(strict_types=1);

namespace App\Livewire\Evaluation;

use App\Actions\Evaluation\DeleteEvaluationAction;
use App\Actions\Evaluation\EvaluateMentorAction;
use App\Models\Evaluation;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

class MentorEvaluationManager extends Component
{
    use WithPagination;

    public bool $showForm = false;

    public ?Evaluation $editingEvaluation = null;

    public string $mentorId = '';

    public ?float $overallScore = null;

    public ?string $feedback = '';

    /** @var array<string, float> */
    public array $criteriaScores = [];

    public function mount(): void
    {
        $this->criteriaScores = [
            'communication' => 0,
            'responsiveness' => 0,
            'guidance_quality' => 0,
        ];
    }

    public function create(): void
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function store(EvaluateMentorAction $action): void
    {
        $this->validate([
            'mentorId' => 'required|exists:users,id',
            'overallScore' => 'required|numeric|min:0|max:100',
            'feedback' => 'nullable|string|max:2000',
            'criteriaScores.*' => 'nullable|numeric|min:0|max:100',
        ]);

        $mentor = User::findOrFail($this->mentorId);

        $action->execute(Auth::user(), $mentor, [
            'overall_score' => $this->overallScore,
            'feedback' => $this->feedback,
            'criteria_scores' => array_filter($this->criteriaScores, fn ($score) => $score > 0),
        ]);

        $this->showForm = false;
        $this->resetForm();
        $this->dispatch('notify', type: 'success', message: 'Mentor evaluation submitted successfully.');
    }

    public function edit(Evaluation $evaluation): void
    {
        $this->editingEvaluation = $evaluation;
        $this->mentorId = $evaluation->mentor_id;
        $this->overallScore = $evaluation->overall_score;
        $this->feedback = $evaluation->feedback;
        $this->criteriaScores = array_merge(
            ['communication' => 0, 'responsiveness' => 0, 'guidance_quality' => 0],
            $evaluation->criteria_scores ?? [],
        );
        $this->showForm = true;
    }

    public function update(EvaluateMentorAction $action): void
    {
        if (! $this->editingEvaluation) {
            return;
        }

        $this->validate([
            'mentorId' => 'required|exists:users,id',
            'overallScore' => 'required|numeric|min:0|max:100',
            'feedback' => 'nullable|string|max:2000',
            'criteriaScores.*' => 'nullable|numeric|min:0|max:100',
        ]);

        $mentor = User::findOrFail($this->mentorId);

        $action->execute(Auth::user(), $mentor, [
            'overall_score' => $this->overallScore,
            'feedback' => $this->feedback,
            'criteria_scores' => array_filter($this->criteriaScores, fn ($score) => $score > 0),
        ]);

        app(DeleteEvaluationAction::class)->execute($this->editingEvaluation);

        $this->showForm = false;
        $this->resetForm();
        $this->dispatch('notify', type: 'success', message: 'Mentor evaluation updated successfully.');
    }

    public function delete(Evaluation $evaluation, DeleteEvaluationAction $action): void
    {
        $action->execute($evaluation);
        $this->dispatch('notify', type: 'success', message: 'Evaluation deleted successfully.');
    }

    public function cancel(): void
    {
        $this->showForm = false;
        $this->resetForm();
    }

    protected function resetForm(): void
    {
        $this->mentorId = '';
        $this->overallScore = null;
        $this->feedback = '';
        $this->criteriaScores = [
            'communication' => 0,
            'responsiveness' => 0,
            'guidance_quality' => 0,
        ];
        $this->editingEvaluation = null;
    }

    #[Layout('layouts::app')]
    public function render()
    {
        $evaluations = Evaluation::query()
            ->with(['mentor', 'evaluator'])
            ->latest()
            ->paginate(10);

        $mentors = User::role('supervisor')->orderBy('name')->get();

        return view('livewire.evaluation.mentor-evaluation-manager', [
            'evaluations' => $evaluations,
            'mentors' => $mentors,
        ]);
    }
}
