<?php

declare(strict_types=1);

namespace App\Livewire\Internship;

use App\Actions\Requirement\CreateRequirementAction;
use App\Actions\Requirement\DeleteRequirementAction;
use App\Actions\Requirement\UpdateRequirementAction;
use App\Models\Document;
use App\Models\Internship;
use App\Models\InternshipDocumentRequirement;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

class RequirementManager extends Component
{
    public string $internshipId = '';

    public bool $requirementModal = false;

    public array $formData = [
        'id' => null,
        'document_id' => '',
        'is_mandatory' => true,
    ];

    public function mount(string $internshipId): void
    {
        $this->internshipId = $internshipId;
    }

    #[Computed]
    public function internship(): Internship
    {
        return Internship::with('documentRequirements.document')->findOrFail($this->internshipId);
    }

    #[Computed]
    public function availableDocuments(): Collection
    {
        return Document::active()->orderBy('name')->get();
    }

    public function add(): void
    {
        $this->resetErrorBag();
        $this->formData = [
            'id' => null,
            'document_id' => '',
            'is_mandatory' => true,
        ];
        $this->requirementModal = true;
    }

    public function edit(InternshipDocumentRequirement $requirement): void
    {
        $this->resetErrorBag();
        $this->formData = [
            'id' => $requirement->id,
            'document_id' => $requirement->document_id,
            'is_mandatory' => $requirement->is_mandatory,
        ];
        $this->requirementModal = true;
    }

    public function save(
        CreateRequirementAction $createAction,
        UpdateRequirementAction $updateAction,
    ): void {
        $this->validate([
            'formData.document_id' => 'required|exists:documents,id',
            'formData.is_mandatory' => 'boolean',
        ]);

        try {
            if ($this->formData['id']) {
                $requirement = InternshipDocumentRequirement::findOrFail($this->formData['id']);
                $updateAction->execute($requirement, $this->formData['document_id'], $this->formData['is_mandatory']);
            } else {
                $createAction->execute($this->internshipId, $this->formData['document_id'], $this->formData['is_mandatory']);
            }

            flash()->success('Requirement saved successfully.');
            $this->requirementModal = false;
        } catch (\RuntimeException $e) {
            flash()->error($e->getMessage());
        }
    }

    public function remove(InternshipDocumentRequirement $requirement, DeleteRequirementAction $action): void
    {
        $action->execute($requirement);
        flash()->success('Requirement removed.');
    }

    public function render()
    {
        return view('livewire.internship.requirement-manager');
    }
}
