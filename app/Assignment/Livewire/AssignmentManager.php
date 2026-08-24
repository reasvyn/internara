<?php

declare(strict_types=1);

namespace App\Assignment\Livewire;

use App\Assignment\Actions\CreateAssignmentAction;
use App\Assignment\Actions\DeleteAssignmentAction;
use App\Assignment\Actions\PublishAssignmentAction;
use App\Assignment\Actions\UpdateAssignmentAction;
use App\Assignment\Data\CreateAssignmentData;
use App\Assignment\Data\UpdateAssignmentData;
use App\Assignment\Models\Assignment;
use App\Core\Exceptions\RejectedException;
use App\Core\Livewire\BaseRecordManager;
use App\Program\Internship\Models\Internship;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use TallStackUi\Traits\Interactions;

class AssignmentManager extends BaseRecordManager
{
    use Interactions;

    public bool $assignmentModal = false;

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
            ['key' => 'title', 'label' => __('assignment.title'), 'sortable' => true],
            ['key' => 'assignment_type', 'label' => __('assignment.type')],
            ['key' => 'internship.name', 'label' => __('assignment.internship')],
            ['key' => 'is_mandatory', 'label' => __('assignment.mandatory')],
            ['key' => 'status', 'label' => __('assignment.status')],
            ['key' => 'due_date', 'label' => __('assignment.due_date'), 'sortable' => true],
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
    public function assignmentTypes(): Collection
    {
        return collect([
            ['id' => 'project', 'name' => 'Project'],
            ['id' => 'report', 'name' => 'Report'],
            ['id' => 'essay', 'name' => 'Essay'],
        ]);
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
        return Internship::query()->orderBy('name')->get(['id', 'name']);
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
                new UpdateAssignmentData(
                    assignmentType: $this->formData['assignment_type'],
                    title: $this->formData['title'],
                    description: $this->formData['description'] ?: null,
                    isMandatory: $this->formData['is_mandatory'],
                    dueDate: $this->formData['due_date'],
                ),
            );
            $this->toast()->success(__('assignment.updated'))->send();
        } else {
            $this->authorize('create', Assignment::class);
            $createAction->execute(new CreateAssignmentData(
                assignmentType: $this->formData['assignment_type'],
                internshipId: $this->formData['internship_id'],
                title: $this->formData['title'],
                description: $this->formData['description'] ?: null,
                isMandatory: $this->formData['is_mandatory'],
                dueDate: $this->formData['due_date'],
            ));
            $this->toast()->success(__('assignment.created'))->send();
        }

        $this->assignmentModal = false;
    }

    public function publish(Assignment $assignment, PublishAssignmentAction $action): void
    {
        $this->authorize('publish', $assignment);
        $action->execute($assignment);
        $this->toast()->success(__('assignment.published'))->send();
    }

    public function askDelete(string $id): void
    {
        $this->confirmActionType = 'delete';
        $this->confirmTarget = $id;
        $this->dialog()
            ->question(__('common.actions.confirm_action'), $this->confirmMessage ?? __('common.actions.confirm_message'))
            ->confirm(text: __('common.actions.confirm'), method: 'confirmAction')
            ->cancel(text: __('common.actions.cancel'))
            ->send();
    }

    public function askDeleteSelected(): void
    {
        $this->confirmActionType = 'deleteSelected';
        $this->dialog()
            ->question(__('common.actions.confirm_action'), $this->confirmMessage ?? __('common.actions.confirm_message'))
            ->confirm(text: __('common.actions.confirm'), method: 'confirmAction')
            ->cancel(text: __('common.actions.cancel'))
            ->send();
    }

    public function confirmAction(DeleteAssignmentAction $action): void
    {
        try {
            if ($this->confirmActionType === 'delete') {
                $assignment = Assignment::findOrFail($this->confirmTarget);
                $this->authorize('delete', $assignment);
                $action->execute($assignment);
                $this->toast()->success(__('assignment.deleted'))->send();
            } elseif ($this->confirmActionType === 'deleteSelected') {
                $this->performBulkAction(__('common.actions.delete'), function ($id) use ($action) {
                    $assignment = Assignment::find($id);
                    if ($assignment && auth()->user()->can('delete', $assignment)) {
                        $action->execute($assignment);
                    }
                });
            }
        } catch (RejectedException $e) {
            $this->toast()->error($e->getMessage())->send();
        }
        $this->confirmTarget = null;
        $this->confirmActionType = '';
    }

    public function render(): View
    {
        return view('assignment.assignment-manager');
    }
}
