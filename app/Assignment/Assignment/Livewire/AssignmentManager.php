<?php

declare(strict_types=1);

namespace App\Assignment\Assignment\Livewire;

use App\Assignment\Assignment\Actions\CreateAssignmentAction;
use App\Assignment\Assignment\Actions\DeleteAssignmentAction;
use App\Assignment\Assignment\Actions\PublishAssignmentAction;
use App\Assignment\Assignment\Actions\UpdateAssignmentAction;
use App\Assignment\Assignment\Models\Assignment;
use App\Assignment\Assignment\Models\AssignmentType;
use App\Core\Livewire\BaseRecordManager;
use App\Program\Internship\Models\Internship;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Computed;

class AssignmentManager extends BaseRecordManager
{
    public bool $assignmentModal = false;

    public array $formData = [
        'id' => null,
        'assignment_type_id' => '',
        'internship_id' => '',
        'title' => '',
        'description' => '',
        'is_mandatory' => false,
        'due_date' => '',
    ];

    public function headers(): array
    {
        return [
            ['key' => 'title', 'label' => 'Title', 'sortable' => true],
            ['key' => 'type.name', 'label' => 'Type'],
            ['key' => 'internship.name', 'label' => 'Internship'],
            ['key' => 'is_mandatory', 'label' => 'Mandatory'],
            ['key' => 'status', 'label' => 'Status'],
            ['key' => 'due_date', 'label' => 'Due Date', 'sortable' => true],
            ['key' => 'actions', 'label' => '', 'sortable' => false],
        ];
    }

    protected function query(): Builder
    {
        return Assignment::query()->with(['type', 'internship']);
    }

    protected function applySearch(Builder $query): Builder
    {
        return $query->where(function ($q) {
            $q->where('title', 'like', "%{$this->search}%")
                ->orWhereHas('type', fn ($t) => $t->where('name', 'like', "%{$this->search}%"))
                ->orWhereHas('internship', fn ($i) => $i->where('name', 'like', "%{$this->search}%"));
        });
    }

    protected function applyFilters(Builder $query): Builder
    {
        return $query
            ->when($this->filters['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->when($this->filters['type_id'] ?? null, fn ($q, $v) => $q->where('assignment_type_id', $v))
            ->when($this->filters['is_mandatory'] ?? null, fn ($q, $v) => $q->where('is_mandatory', $v === 'yes'));
    }

    #[Computed]
    public function assignmentTypes()
    {
        return AssignmentType::all();
    }

    #[Computed]
    public function internships()
    {
        return Internship::all();
    }

    public function create(): void
    {
        $this->resetErrorBag();
        $this->formData = [
            'id' => null,
            'assignment_type_id' => '',
            'internship_id' => '',
            'title' => '',
            'description' => '',
            'is_mandatory' => false,
            'due_date' => '',
        ];
        $this->assignmentModal = true;
    }

    public function edit(Assignment $assignment): void
    {
        $this->resetErrorBag();
        $this->formData = [
            'id' => $assignment->id,
            'assignment_type_id' => $assignment->assignment_type_id,
            'internship_id' => $assignment->internship_id,
            'title' => $assignment->title,
            'description' => $assignment->description,
            'is_mandatory' => $assignment->is_mandatory,
            'due_date' => $assignment->due_date?->format('Y-m-d'),
        ];
        $this->assignmentModal = true;
    }

    public function save(
        CreateAssignmentAction $createAction,
        UpdateAssignmentAction $updateAction,
    ): void {
        $rules = [
            'formData.assignment_type_id' => 'required|exists:assignment_types,id',
            'formData.internship_id' => 'required|exists:internships,id',
            'formData.title' => 'required|string|max:255',
            'formData.due_date' => 'required|date',
        ];

        $this->validate($rules);

        if ($this->formData['id']) {
            $assignment = Assignment::findOrFail($this->formData['id']);
            $updateAction->execute(
                $assignment,
                title: $this->formData['title'],
                description: $this->formData['description'] ?: null,
                isMandatory: $this->formData['is_mandatory'],
                dueDate: $this->formData['due_date'],
            );
            flash()->success('Assignment updated.');
        } else {
            $createAction->execute(
                assignmentTypeId: $this->formData['assignment_type_id'],
                internshipId: $this->formData['internship_id'],
                title: $this->formData['title'],
                description: $this->formData['description'] ?: null,
                isMandatory: $this->formData['is_mandatory'],
                dueDate: $this->formData['due_date'],
            );
            flash()->success('Assignment created.');
        }

        $this->assignmentModal = false;
    }

    public function publish(Assignment $assignment, PublishAssignmentAction $action): void
    {
        $this->authorize('publish', $assignment);
        $action->execute($assignment);
        flash()->success('Assignment published.');
    }

    public function delete(Assignment $assignment, DeleteAssignmentAction $action): void
    {
        $this->authorize('delete', $assignment);
        $action->execute($assignment);
        flash()->success('Assignment deleted.');
    }

    public function deleteSelected(DeleteAssignmentAction $action): void
    {
        $this->performBulkAction('Delete', function ($id) use ($action) {
            $assignment = Assignment::find($id);
            if ($assignment && auth()->user()->can('delete', $assignment)) {
                $action->execute($assignment);
            }
        });
    }

    public function render(): View
    {
        return view('assignment.assignment.assignment-manager');
    }
}
