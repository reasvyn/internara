<?php

declare(strict_types=1);

namespace App\Livewire\Assignment\Admin;

use App\Domain\Assignment\Actions\CreateAssignmentAction;
use App\Domain\Assignment\Actions\DeleteAssignmentAction;
use App\Domain\Assignment\Actions\PublishAssignmentAction;
use App\Domain\Assignment\Models\Assignment;
use App\Domain\Assignment\Models\AssignmentType;
use App\Domain\Internship\Models\Internship;
use Illuminate\View\View;
use Livewire\Component;

/**
 * Admin UI for managing assignments.
 *
 * S2 - Sustain: Clear CRUD operations.
 */
class ManageAssignments extends Component
{
    // Form state
    public ?string $assignmentTypeId = null;

    public ?string $internshipId = null;

    public string $title = '';

    public string $description = '';

    public bool $isMandatory = false;

    public string $dueDate = '';

    public string $academicYear = '';

    // UI state
    public bool $showForm = false;

    public ?string $editingId = null;

    public function render(): View
    {
        return view('livewire.assignment.manage-assignments', [
            'assignments' => Assignment::with(['type', 'internship'])->get(),
            'types' => AssignmentType::all(),
            'internships' => Internship::all(),
        ]);
    }

    public function create(CreateAssignmentAction $action): void
    {
        $this->validate([
            'assignmentTypeId' => 'required|exists:assignment_types,id',
            'internshipId' => 'required|exists:internships,id',
            'title' => 'required|string|max:255',
            'dueDate' => 'nullable|date',
        ]);

        $action->execute(
            assignmentTypeId: $this->assignmentTypeId,
            internshipId: $this->internshipId,
            title: $this->title,
            description: $this->description ?: null,
            isMandatory: $this->isMandatory,
            dueDate: $this->dueDate ?: null,
            config: [],
        );

        $this->resetForm();
        $this->dispatch('swal:success', message: 'Assignment created successfully.');
    }

    public function publish(string $id, PublishAssignmentAction $action): void
    {
        $assignment = Assignment::findOrFail($id);
        $action->execute($assignment);

        $this->dispatch('swal:success', message: 'Assignment published.');
    }

    public function delete(string $id, DeleteAssignmentAction $action): void
    {
        $assignment = Assignment::findOrFail($id);
        $action->execute($assignment);

        $this->dispatch('swal:success', message: 'Assignment deleted.');
    }

    public function resetForm(): void
    {
        $this->reset([
            'assignmentTypeId',
            'internshipId',
            'title',
            'description',
            'isMandatory',
            'dueDate',
            'academicYear',
        ]);
        $this->showForm = false;
        $this->editingId = null;
    }
}
