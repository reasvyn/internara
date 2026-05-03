declare(strict_types=1);

namespace App\Livewire\Assignment\Admin;

use App\Domain\Assignment\Actions\CreateAssignmentAction;
use App\Domain\Assignment\Actions\DeleteAssignmentAction;
use App\Domain\Assignment\Actions\UpdateAssignmentAction;
use App\Domain\Internship\Models\Internship;
use App\Livewire\BaseRecordManager;
use App\Domain\Assignment\Models\Assignment;
use App\Domain\Assignment\Models\AssignmentType;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Computed;

/**
 * Modernized Assignment Manager using BaseRecordManager pattern.
 */
class AssignmentIndex extends BaseRecordManager
{
    public bool $showModal = false;

    public array $formData = [
        'id' => null,
        'assignment_type_id' => '',
        'internship_id' => '',
        'title' => '',
        'description' => '',
        'group' => 'homework',
        'is_mandatory' => false,
        'due_date' => '',
    ];

    /**
     * Define columns and sorting.
     */
    public function headers(): array
    {
        return [
            ['key' => 'id', 'label' => '#', 'class' => 'w-1'],
            ['key' => 'title', 'label' => 'Title', 'sortable' => true],
            ['key' => 'type.name', 'label' => 'Type'],
            ['key' => 'internship.name', 'label' => 'Internship'],
            ['key' => 'due_date', 'label' => 'Due Date', 'sortable' => true],
            ['key' => 'is_mandatory', 'label' => 'Mandatory'],
            ['key' => 'actions', 'label' => ''],
        ];
    }

    /**
     * Base query for assignments.
     */
    protected function query(): Builder
    {
        return Assignment::query()->with(['type', 'internship']);
    }

    /**
     * Search implementation.
     */
    protected function applySearch(Builder $query): Builder
    {
        return $query->where('title', 'like', "%{$this->search}%");
    }

    /**
     * Filter implementation.
     */
    protected function applyFilters(Builder $query): Builder
    {
        return $query
            ->when($this->filters['internship_id'] ?? null, function ($q, $internshipId) {
                $q->where('internship_id', $internshipId);
            })
            ->when($this->filters['type_id'] ?? null, function ($q, $typeId) {
                $q->where('assignment_type_id', $typeId);
            });
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

    // --- Record Actions ---

    public function create(): void
    {
        $this->resetErrorBag();
        $this->formData = [
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
        $this->formData = [
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

    public function save(
        CreateAssignmentAction $createAction,
        UpdateAssignmentAction $updateAction,
    ): void {
        $this->validate([
            'formData.assignment_type_id' => 'required|exists:assignment_types,id',
            'formData.internship_id' => 'required|exists:internships,id',
            'formData.title' => 'required|string|max:255',
            'formData.due_date' => 'required|date',
        ]);

        if ($this->formData['id']) {
            $assignment = Assignment::findOrFail($this->formData['id']);
            $updateAction->execute($assignment, $this->formData);
            $this->success('Assignment updated.');
        } else {
            $createAction->execute(
                $this->formData['assignment_type_id'],
                $this->formData['internship_id'],
                $this->formData['title'],
                $this->formData['description'],
                $this->formData['group'],
                null,
                $this->formData['is_mandatory'],
                ['due_date' => $this->formData['due_date']],
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

    // --- Bulk Actions ---

    public function deleteSelected(DeleteAssignmentAction $action): void
    {
        $this->performBulkAction('Delete', function ($id) use ($action) {
            $assignment = Assignment::find($id);
            if ($assignment) {
                $action->execute($assignment);
            }
        });
    }

    public function render()
    {
        return view('livewire.admin.assignment.index');
    }
}
