<?php

declare(strict_types=1);

namespace App\Livewire\Assessment;

use App\Enums\Assessment\EvaluatorRole;
use App\Models\Competency;
use App\Models\Indicator;
use App\Models\Rubric;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Mary\Traits\Toast;

class RubricManager extends Component
{
    use Toast;

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

    public function saveRubric(): void
    {
        $this->validate([
            'rubricForm.name' => 'required|string|max:255',
            'rubricForm.description' => 'nullable|string|max:5000',
            'rubricForm.is_active' => 'boolean',
        ]);

        if ($this->rubricForm['id']) {
            Rubric::findOrFail($this->rubricForm['id'])->update([
                'name' => $this->rubricForm['name'],
                'description' => $this->rubricForm['description'],
                'is_active' => $this->rubricForm['is_active'],
            ]);
            $this->success('Rubric updated.');
        } else {
            Rubric::create([
                'name' => $this->rubricForm['name'],
                'description' => $this->rubricForm['description'],
                'is_active' => $this->rubricForm['is_active'],
                'created_by' => auth()->id(),
            ]);
            $this->success('Rubric created.');
        }

        $this->rubricModal = false;
    }

    public function removeRubric(Rubric $rubric): void
    {
        $rubric->delete();
        $this->success('Rubric removed.');
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

    public function saveCompetency(): void
    {
        $this->validate([
            'competencyForm.name' => 'required|string|max:255',
            'competencyForm.description' => 'nullable|string|max:5000',
            'competencyForm.weight' => 'required|integer|min:0|max:100',
            'competencyForm.evaluator_role' => 'required|string|in:'.implode(',', array_column(EvaluatorRole::cases(), 'value')),
            'competencyForm.order' => 'required|integer|min:0',
        ]);

        if ($this->competencyForm['id']) {
            Competency::findOrFail($this->competencyForm['id'])->update([
                'name' => $this->competencyForm['name'],
                'description' => $this->competencyForm['description'],
                'weight' => $this->competencyForm['weight'],
                'evaluator_role' => $this->competencyForm['evaluator_role'],
                'order' => $this->competencyForm['order'],
            ]);
            $this->success('Competency updated.');
        } else {
            Competency::create([
                'rubric_id' => $this->selectedRubricId,
                'name' => $this->competencyForm['name'],
                'description' => $this->competencyForm['description'],
                'weight' => $this->competencyForm['weight'],
                'evaluator_role' => $this->competencyForm['evaluator_role'],
                'order' => $this->competencyForm['order'],
            ]);
            $this->success('Competency created.');
        }

        $this->competencyModal = false;
    }

    public function removeCompetency(Competency $competency): void
    {
        $competency->delete();
        $this->success('Competency removed.');
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

    public function saveIndicator(): void
    {
        $this->validate([
            'indicatorForm.name' => 'required|string|max:255',
            'indicatorForm.description' => 'nullable|string|max:5000',
            'indicatorForm.max_score' => 'required|numeric|min:1|max:999',
            'indicatorForm.weight' => 'required|integer|min:0|max:100',
            'indicatorForm.order' => 'required|integer|min:0',
        ]);

        if ($this->indicatorForm['id']) {
            Indicator::findOrFail($this->indicatorForm['id'])->update([
                'name' => $this->indicatorForm['name'],
                'description' => $this->indicatorForm['description'],
                'max_score' => $this->indicatorForm['max_score'],
                'weight' => $this->indicatorForm['weight'],
                'order' => $this->indicatorForm['order'],
            ]);
            $this->success('Indicator updated.');
        } else {
            Indicator::create([
                'competency_id' => $this->selectedCompetencyId,
                'name' => $this->indicatorForm['name'],
                'description' => $this->indicatorForm['description'],
                'max_score' => $this->indicatorForm['max_score'],
                'weight' => $this->indicatorForm['weight'],
                'order' => $this->indicatorForm['order'],
            ]);
            $this->success('Indicator created.');
        }

        $this->indicatorModal = false;
    }

    public function removeIndicator(Indicator $indicator): void
    {
        $indicator->delete();
        $this->success('Indicator removed.');
    }

    public function render()
    {
        return view('livewire.assessment.rubric-manager');
    }
}
