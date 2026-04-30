<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Assignment;

use App\Actions\Assignment\CreateAssignmentAction;
use App\Actions\Assignment\UpdateAssignmentAction;
use App\Actions\Assignment\DeleteAssignmentAction;
use App\Models\Assignment;
use App\Models\AssignmentType;
use App\Models\Internship;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;

class AssignmentIndex extends Component
{
    use WithPagination, Toast;

    public bool $showModal = false;
    public array $assignmentData = [
        'id' => null,
        'assignment_type_id' => '',
        'internship_id' => '',
        'title' => '',
        'description' => '',
        'group' => 'homework',
        'is_mandatory' => false,
        'due_date' => '',
    ];

    public string $search = '';

    public function headers(): array
    {
        return [
            ['key' => 'id', 'label' => '#', 'class' => 'w-1'],
            ['key' => 'title', 'label' => 'Title', 'sortable' => true],
            ['key' => 'type.name', 'label' => 'Type'],
            ['key' => 'internship.name', 'label' => 'Internship'],
            ['key' => 'due_date', 'label' => 'Due Date'],
            ['key' => 'is_mandatory', 'label' => 'Mandatory'],
        ];
    }

    public function assignments(): LengthAwarePaginator
    {
        return Assignment::query()
            ->with(['type', 'internship'])
            ->when($this->search, fn ($q) => $q->where('title', 'like', "%{$this->search}%"))
            ->latest()
            ->paginate(10);
    }

    public function create(): void
    {
        $this->resetErrorBag();
        $this->assignmentData = [
            'id' => null,
            'assignment_type_id' => '',
            'internship_id' => '',
            'title' => '',
            'description' => '',
            'group' => 'homework',
            'is_mandatory' => false,
            'due_date' => '',
        ];
        $this->showModal = true;
    }

    public function edit(Assignment $assignment): void
    {
        $this->resetErrorBag();
        $this->assignmentData = [
            'id' => $assignment->id,
            'assignment_type_id' => $assignment->assignment_type_id,
            'internship_id' => $assignment->internship_id,
            'title' => $assignment->title,
            'description' => $assignment->description,
            'group' => $assignment->group,
            'is_mandatory' => $assignment->is_mandatory,
            'due_date' => $assignment->due_date?->format('Y-m-d'),
        ];
        $this->showModal = true;
    }

    public function save(CreateAssignmentAction $createAction, UpdateAssignmentAction $updateAction): void
    {
        $this->validate([
            'assignmentData.assignment_type_id' => 'required|exists:assignment_types,id',
            'assignmentData.internship_id' => 'required|exists:internships,id',
            'assignmentData.title' => 'required|string|max:255',
            'assignmentData.due_date' => 'required|date',
        ]);

        if ($this->assignmentData['id']) {
            $assignment = Assignment::findOrFail($this->assignmentData['id']);
            $updateAction->execute($assignment, $this->assignmentData);
            $this->success('Assignment updated.');
        } else {
            $createAction->execute(
                $this->assignmentData['assignment_type_id'],
                $this->assignmentData['internship_id'],
                $this->assignmentData['title'],
                $this->assignmentData['description'],
                $this->assignmentData['group'],
                null,
                $this->assignmentData['is_mandatory'],
                ['due_date' => $this->assignmentData['due_date']]
            );
            $this->success('Assignment created.');
        }

        $this->showModal = false;
    }

    public function delete(Assignment $assignment, DeleteAssignmentAction $action): void
    {
        $action->execute($assignment);
        $this->success('Assignment deleted.');
    }

    public function render()
    {
        return view('livewire.admin.assignment.index', [
            'assignments' => $this->assignments(),
            'headers' => $this->headers(),
            'types' => AssignmentType::all(),
            'internships' => Internship::all(),
        ]);
    }
}