<?php

declare(strict_types=1);

namespace App\Livewire\Internship;

use App\Models\Document;
use App\Models\Internship;
use App\Models\InternshipDocumentRequirement;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Mary\Traits\Toast;

class RequirementManager extends Component
{
    use Toast;

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

    public function save(): void
    {
        $this->validate([
            'formData.document_id' => 'required|exists:documents,id',
            'formData.is_mandatory' => 'boolean',
        ]);

        $exists = InternshipDocumentRequirement::where('internship_id', $this->internshipId)
            ->where('document_id', $this->formData['document_id'])
            ->when($this->formData['id'], fn ($q) => $q->where('id', '!=', $this->formData['id']))
            ->exists();

        if ($exists) {
            $this->error('This document is already a requirement for this internship.');

            return;
        }

        if ($this->formData['id']) {
            InternshipDocumentRequirement::findOrFail($this->formData['id'])->update([
                'document_id' => $this->formData['document_id'],
                'is_mandatory' => $this->formData['is_mandatory'],
            ]);
        } else {
            $maxSort = InternshipDocumentRequirement::where('internship_id', $this->internshipId)
                ->max('sort_order') ?? 0;

            InternshipDocumentRequirement::create([
                'internship_id' => $this->internshipId,
                'document_id' => $this->formData['document_id'],
                'is_mandatory' => $this->formData['is_mandatory'],
                'sort_order' => $maxSort + 1,
            ]);
        }

        $this->success('Requirement saved successfully.');
        $this->requirementModal = false;
    }

    public function remove(InternshipDocumentRequirement $requirement): void
    {
        $requirement->delete();
        $this->success('Requirement removed.');
    }

    public function render()
    {
        return view('livewire.internship.requirement-manager');
    }
}
