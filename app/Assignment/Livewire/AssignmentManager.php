<?php

declare(strict_types=1);

namespace App\Assignment\Livewire;

use App\Assignment\Actions\CreateAssignmentAction;
use App\Assignment\Actions\DeleteAssignmentAction;
use App\Assignment\Actions\PublishAssignmentAction;
use App\Assignment\Actions\UpdateAssignmentAction;
use App\Assignment\Models\Assignment;
use App\Core\Exceptions\RejectedException;
use App\Core\Livewire\BaseRecordManager;
use App\Program\Internship\Models\Internship;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Computed;

class AssignmentManager extends BaseRecordManager
{
    public bool $assignmentModal = false;

    public bool $showConfirm = false;

    public string $confirmActionType = '';

    public ?string $confirmTarget = null;

    public array $formData = [
        'id' => null,
        'assignment_type' => '',
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
            ['key' => 'assignment_type', 'label' => 'Type'],
            ['key' => 'internship.name', 'label' => 'Internship'],
            ['key' => 'is_mandatory', 'label' => 'Mandatory'],
            ['key' => 'status', 'label' => 'Status'],
            ['key' => 'due_date', 'label' => 'Due Date', 'sortable' => true],
            ['key' => 'actions', 'label' => '', 'sortable' => false],
        ];
    }

    protected function query(): Builder
    {
        return Assignment::query()->with(['internship']);
    }

    protected function applySearch(Builder $query): Builder
    {
        return $query->where(function ($q) {
            $q->where('title', 'like', "%{$this->search}%")
                ->orWhere('assignment_type', 'like', "%{$this->search}%")
                ->orWhereHas(
                    'internship',
                    fn ($i) => $i->where('name', 'like', "%{$this->search}%"),
                );
        });
    }

    protected function applyFilters(Builder $query): Builder
    {
        return $query
            ->when($this->filters['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->when(
                $this->filters['assignment_type'] ?? null,
                fn ($q, $v) => $q->where('assignment_type', $v),
            )
            ->when(
                $this->filters['is_mandatory'] ?? null,
                fn ($q, $v) => $q->where('is_mandatory', $v === 'yes'),
            );
    }

    #[Computed]
    public function assignmentTypeOptions(): array
    {
        return [
            ['id' => 'project', 'name' => 'Project'],
            ['id' => 'report', 'name' => 'Report'],
            ['id' => 'essay', 'name' => 'Essay'],
        ];
    }

    #[Computed]
    public function internships()
    {
        return Internship::all();
    }

    public function create(): void
    {
        $this->authorize('create', Assignment::class);
        $this->resetErrorBag();
        $this->formData = [
            'id' => null,
            'assignment_type' => '',
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
        $this->authorize('update', $assignment);
        $this->resetErrorBag();
        $this->formData = [
            'id' => $assignment->id,
            'assignment_type' => $assignment->assignment_type,
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
            'formData.assignment_type' => 'required|string|in:project,report,essay',
            'formData.internship_id' => 'required|exists:internships,id',
            'formData.title' => 'required|string|max:255',
            'formData.due_date' => 'required|date',
        ];

        $this->validate($rules);

        if ($this->formData['id']) {
            $assignment = Assignment::findOrFail($this->formData['id']);
            $this->authorize('update', $assignment);
            $updateAction->execute(
                $assignment,
                assignmentType: $this->formData['assignment_type'],
                title: $this->formData['title'],
                description: $this->formData['description'] ?: null,
                isMandatory: $this->formData['is_mandatory'],
                dueDate: $this->formData['due_date'],
            );
            flash()->success('Assignment updated.');
        } else {
            $this->authorize('create', Assignment::class);
            $createAction->execute(
                assignmentType: $this->formData['assignment_type'],
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

    public function askDelete(string $id): void
    {
        $this->confirmActionType = 'delete';
        $this->confirmTarget = $id;
        $this->showConfirm = true;
    }

    public function askDeleteSelected(): void
    {
        $this->confirmActionType = 'deleteSelected';
        $this->showConfirm = true;
    }

    public function confirmAction(DeleteAssignmentAction $action): void
    {
        try {
            if ($this->confirmActionType === 'delete') {
                $assignment = Assignment::findOrFail($this->confirmTarget);
                $this->authorize('delete', $assignment);
                $action->execute($assignment);
                flash()->success('Assignment deleted.');
            } elseif ($this->confirmActionType === 'deleteSelected') {
                $this->performBulkAction('Delete', function ($id) use ($action) {
                    $assignment = Assignment::find($id);
                    if ($assignment && auth()->user()->can('delete', $assignment)) {
                        $action->execute($assignment);
                    }
                });
            }
        } catch (RejectedException $e) {
            flash()->error($e->getMessage());
        }

        $this->showConfirm = false;
        $this->confirmTarget = null;
        $this->confirmActionType = '';
    }

    public function render(): View
    {
        return view('assignment.assignment-manager');
    }
}
