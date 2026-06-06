<?php

declare(strict_types=1);

namespace App\Assessment\Rubric\Livewire;

use App\Assessment\Rubric\Actions\CreateCompetencyAction;
use App\Assessment\Rubric\Actions\CreateIndicatorAction;
use App\Assessment\Rubric\Actions\CreateRubricAction;
use App\Assessment\Rubric\Actions\DeleteCompetencyAction;
use App\Assessment\Rubric\Actions\DeleteIndicatorAction;
use App\Assessment\Rubric\Actions\DeleteRubricAction;
use App\Assessment\Rubric\Actions\UpdateCompetencyAction;
use App\Assessment\Rubric\Actions\UpdateIndicatorAction;
use App\Assessment\Rubric\Actions\UpdateRubricAction;
use App\Assessment\Rubric\Models\Competency;
use App\Assessment\Rubric\Models\Indicator;
use App\Assessment\Rubric\Models\Rubric;
use App\Evaluation\Enums\EvaluatorRole;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

class RubricManager extends Component
{
    public bool $rubricModal = false;

    public bool $competencyModal = false;

    public bool $indicatorModal = false;

    public ?string $selectedRubricId = null;

    public ?string $selectedCompetencyId = null;

    public array $rubricForm = [
        'id' => null,
        'name' => '',
        'description' => '',
        'is_active' => true,
    ];

    public array $competencyForm = [
        'id' => null,
        'name' => '',
        'description' => '',
        'weight' => 0,
        'evaluator_role' => '',
        'order' => 0,
    ];

    public array $indicatorForm = [
        'id' => null,
        'name' => '',
        'description' => '',
        'max_score' => 100,
        'weight' => 0,
        'order' => 0,
    ];

    #[Computed]
    public function rubrics(): Collection
    {
        return Rubric::with('competencies.indicators')->latest()->get();
    }

    #[Computed]
    public function evaluatorRoles(): array
    {
        return collect(EvaluatorRole::cases())->map(fn ($role) => [
            'id' => $role->value,
            'name' => $role->label(),
        ])->toArray();
    }

    public function addRubric(): void
    {
        $this->resetErrorBag();
        $this->rubricForm = ['id' => null, 'name' => '', 'description' => '', 'is_active' => true];
        $this->rubricModal = true;
    }

    public function editRubric(Rubric $rubric): void
    {
        $this->resetErrorBag();
        $this->rubricForm = [
            'id' => $rubric->id,
            'name' => $rubric->name,
            'description' => $rubric->description ?? '',
            'is_active' => $rubric->is_active,
        ];
        $this->rubricModal = true;
    }

    public function saveRubric(CreateRubricAction $createAction, UpdateRubricAction $updateAction): void
    {
        $this->validate([
            'rubricForm.name' => 'required|string|max:255',
            'rubricForm.description' => 'nullable|string|max:5000',
            'rubricForm.is_active' => 'boolean',
        ]);

        if ($this->rubricForm['id']) {
            $rubric = Rubric::findOrFail($this->rubricForm['id']);
            $updateAction->execute(
                rubric: $rubric,
                name: $this->rubricForm['name'],
                description: $this->rubricForm['description'],
                isActive: $this->rubricForm['is_active'],
            );
            flash()->success('Rubric updated.');
        } else {
            $createAction->execute(
                name: $this->rubricForm['name'],
                description: $this->rubricForm['description'],
                isActive: $this->rubricForm['is_active'],
            );
            flash()->success('Rubric created.');
        }

        $this->rubricModal = false;
    }

    public function removeRubric(Rubric $rubric, DeleteRubricAction $action): void
    {
        $action->execute($rubric);
        flash()->success('Rubric removed.');
    }

    public function addCompetency(string $rubricId): void
    {
        $this->resetErrorBag();
        $this->selectedRubricId = $rubricId;
        $this->competencyForm = ['id' => null, 'name' => '', 'description' => '', 'weight' => 0, 'evaluator_role' => '', 'order' => 0];
        $this->competencyModal = true;
    }

    public function editCompetency(Competency $competency): void
    {
        $this->resetErrorBag();
        $this->selectedRubricId = $competency->rubric_id;
        $this->competencyForm = [
            'id' => $competency->id,
            'name' => $competency->name,
            'description' => $competency->description ?? '',
            'weight' => $competency->weight,
            'evaluator_role' => $competency->evaluator_role->value,
            'order' => $competency->order,
        ];
        $this->competencyModal = true;
    }

    public function saveCompetency(CreateCompetencyAction $createAction, UpdateCompetencyAction $updateAction): void
    {
        $this->validate([
            'competencyForm.name' => 'required|string|max:255',
            'competencyForm.description' => 'nullable|string|max:5000',
            'competencyForm.weight' => 'required|integer|min:0|max:100',
            'competencyForm.evaluator_role' => 'required|string|in:'.implode(',', array_column(EvaluatorRole::cases(), 'value')),
            'competencyForm.order' => 'required|integer|min:0',
        ]);

        $evaluatorRole = EvaluatorRole::tryFrom($this->competencyForm['evaluator_role']) ?? EvaluatorRole::TEACHER;

        if ($this->competencyForm['id']) {
            $competency = Competency::findOrFail($this->competencyForm['id']);
            $updateAction->execute(
                competency: $competency,
                name: $this->competencyForm['name'],
                description: $this->competencyForm['description'],
                weight: (int) $this->competencyForm['weight'],
                evaluatorRole: $evaluatorRole,
                order: (int) $this->competencyForm['order'],
            );
            flash()->success('Competency updated.');
        } else {
            $createAction->execute(
                rubricId: $this->selectedRubricId,
                name: $this->competencyForm['name'],
                description: $this->competencyForm['description'],
                weight: (int) $this->competencyForm['weight'],
                evaluatorRole: $evaluatorRole,
                order: (int) $this->competencyForm['order'],
            );
            flash()->success('Competency created.');
        }

        $this->competencyModal = false;
    }

    public function removeCompetency(Competency $competency, DeleteCompetencyAction $action): void
    {
        $action->execute($competency);
        flash()->success('Competency removed.');
    }

    public function addIndicator(string $competencyId): void
    {
        $this->resetErrorBag();
        $this->selectedCompetencyId = $competencyId;
        $this->indicatorForm = ['id' => null, 'name' => '', 'description' => '', 'max_score' => 100, 'weight' => 0, 'order' => 0];
        $this->indicatorModal = true;
    }

    public function editIndicator(Indicator $indicator): void
    {
        $this->resetErrorBag();
        $this->selectedCompetencyId = $indicator->competency_id;
        $this->indicatorForm = [
            'id' => $indicator->id,
            'name' => $indicator->name,
            'description' => $indicator->description ?? '',
            'max_score' => $indicator->max_score,
            'weight' => $indicator->weight,
            'order' => $indicator->order,
        ];
        $this->indicatorModal = true;
    }

    public function saveIndicator(CreateIndicatorAction $createAction, UpdateIndicatorAction $updateAction): void
    {
        $this->validate([
            'indicatorForm.name' => 'required|string|max:255',
            'indicatorForm.description' => 'nullable|string|max:5000',
            'indicatorForm.max_score' => 'required|numeric|min:1|max:999',
            'indicatorForm.weight' => 'required|integer|min:0|max:100',
            'indicatorForm.order' => 'required|integer|min:0',
        ]);

        if ($this->indicatorForm['id']) {
            $indicator = Indicator::findOrFail($this->indicatorForm['id']);
            $updateAction->execute(
                indicator: $indicator,
                name: $this->indicatorForm['name'],
                description: $this->indicatorForm['description'],
                maxScore: (int) $this->indicatorForm['max_score'],
                weight: (int) $this->indicatorForm['weight'],
                order: (int) $this->indicatorForm['order'],
            );
            flash()->success('Indicator updated.');
        } else {
            $createAction->execute(
                competencyId: $this->selectedCompetencyId,
                name: $this->indicatorForm['name'],
                description: $this->indicatorForm['description'],
                maxScore: (int) $this->indicatorForm['max_score'],
                weight: (int) $this->indicatorForm['weight'],
                order: (int) $this->indicatorForm['order'],
            );
            flash()->success('Indicator created.');
        }

        $this->indicatorModal = false;
    }

    public function removeIndicator(Indicator $indicator, DeleteIndicatorAction $action): void
    {
        $action->execute($indicator);
        flash()->success('Indicator removed.');
    }

    public function render(): View
    {
        return view('assessment.rubric.rubric-manager');
    }
}
