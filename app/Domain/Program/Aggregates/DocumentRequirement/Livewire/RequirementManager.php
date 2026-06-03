<?php

declare(strict_types=1);

namespace App\Domain\Program\Aggregates\DocumentRequirement\Livewire;

use App\Domain\Certification\Aggregates\Document\Models\Document;
use App\Domain\Program\Aggregates\DocumentRequirement\Actions\CreateRequirementAction;
use App\Domain\Program\Aggregates\DocumentRequirement\Actions\DeleteRequirementAction;
use App\Domain\Program\Aggregates\DocumentRequirement\Actions\UpdateRequirementAction;
use App\Domain\Program\Aggregates\DocumentRequirement\Livewire\Forms\InternshipRequirementForm;
use App\Domain\Program\Aggregates\Internship\Models\Internship;
use App\Domain\Program\Aggregates\Internship\Models\InternshipDocumentRequirement;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Computed;
use Livewire\Component;

class RequirementManager extends Component
{
    use AuthorizesRequests;

    public string $internshipId = '';

    public function boot(): void
    {
        $this->authorize('viewAny', Internship::class);
    }

    public bool $requirementModal = false;

    public InternshipRequirementForm $form;

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
        $this->form->reset();
        $this->requirementModal = true;
    }

    public function edit(string $id): void
    {
        $requirement = InternshipDocumentRequirement::findOrFail($id);

        $this->resetErrorBag();
        $this->form->fill([
            'id' => $requirement->id,
            'document_id' => $requirement->document_id,
            'is_mandatory' => $requirement->is_mandatory,
        ]);
        $this->requirementModal = true;
    }

    public function save(
        CreateRequirementAction $createAction,
        UpdateRequirementAction $updateAction,
    ): void {
        $this->form->validate();

        try {
            if ($this->form->id) {
                $requirement = InternshipDocumentRequirement::findOrFail($this->form->id);
                $updateAction->execute($requirement, $this->form->document_id, $this->form->is_mandatory);
            } else {
                $createAction->execute($this->internshipId, $this->form->document_id, $this->form->is_mandatory);
            }

            flash()->success(__('internship.requirement_saved'));
            $this->requirementModal = false;
        } catch (\RuntimeException $e) {
            flash()->error($e->getMessage());
        }
    }

    public function remove(string $id, DeleteRequirementAction $action): void
    {
        $requirement = InternshipDocumentRequirement::findOrFail($id);
        $action->execute($requirement);
        flash()->success(__('internship.requirement_removed'));
    }

    public function render(): View
    {
        return view('internship.requirement-manager');
    }
}
