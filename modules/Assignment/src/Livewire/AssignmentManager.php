<?php

declare(strict_types=1);

namespace Modules\Assignment\Livewire;

use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithPagination;
use Modules\Assignment\Models\Assignment;
use Modules\Assignment\Models\AssignmentType;
use Modules\Assignment\Services\Contracts\AssignmentService;

/**
 * Class AssignmentManager
 *
 * Allows administrators to manage internship assignments and policies.
 */
class AssignmentManager extends Component
{
    use WithPagination;

    public bool $formModal = false;

    public ?string $recordId = null;

    #[Validate('required|string|max:255')]
    public string $title = '';

    #[Validate('required|exists:assignment_types,id')]
    public string $assignment_type_id = '';

    #[Validate('nullable|string')]
    public string $description = '';

    #[Validate('boolean')]
    public bool $is_mandatory = true;

    #[Validate('nullable|date')]
    public $due_date;

    /**
     * Reset the form fields.
     */
    public function resetForm(): void
    {
        $this->recordId = null;
        $this->title = '';
        $this->assignment_type_id = '';
        $this->description = '';
        $this->is_mandatory = true;
        $this->due_date = null;
    }

    /**
     * Show the form to create a new assignment.
     */
    public function add(): void
    {
        $this->resetForm();
        $this->formModal = true;
    }

    /**
     * Show the form to edit an existing assignment.
     */
    public function edit(string $id): void
    {
        $assignment = Assignment::findOrFail($id);
        $this->recordId = $id;
        $this->title = $assignment->title;
        $this->assignment_type_id = $assignment->assignment_type_id;
        $this->description = $assignment->description ?? '';
        $this->is_mandatory = $assignment->is_mandatory;
        $this->due_date = $assignment->due_date?->format('Y-m-d\TH:i');

        $this->formModal = true;
    }

    /**
     * Save the assignment record.
     */
    public function save(AssignmentService $service): void
    {
        $this->validate();

        $data = [
            'title' => $this->title,
            'assignment_type_id' => $this->assignment_type_id,
            'description' => $this->description,
            'is_mandatory' => $this->is_mandatory,
            'due_date' => $this->due_date,
        ];

        if ($this->recordId) {
            $service->update($this->recordId, $data);
            notify(__('assignment::ui.success_updated'), 'success');
        } else {
            $service->create($data);
            notify(__('assignment::ui.success_created'), 'success');
        }

        $this->formModal = false;
    }

    /**
     * Delete an assignment.
     */
    public function remove(string $id, AssignmentService $service): void
    {
        $service->delete($id);
        notify(__('assignment::ui.success_deleted'), 'success');
    }

    /**
     * Render the component.
     */
    public function render()
    {
        return view('assignment::livewire.assignment-manager', [
            'assignments' => Assignment::with('type')->latest()->paginate(10),
            'types' => AssignmentType::all(),
        ]);
    }
}
