declare(strict_types=1);

namespace App\Livewire\School;

use App\Domain\School\Actions\CreateDepartmentAction;
use App\Domain\School\Actions\DeleteDepartmentAction;
use App\Domain\School\Actions\UpdateDepartmentAction;
use App\Livewire\BaseRecordManager;
use App\Domain\School\Models\Department;
use App\Domain\School\Models\School;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Layout;

/**
 * Modernized Department Manager using BaseRecordManager pattern.
 */
class DepartmentIndex extends BaseRecordManager
{
    public bool $showModal = false;

    public array $formData = [
        'id' => null,
        'name' => '',
        'description' => '',
    ];

    /**
     * Define columns and sorting.
     */
    public function headers(): array
    {
        return [
            ['key' => 'name', 'label' => __('department.name'), 'sortable' => true],
            ['key' => 'description', 'label' => __('department.description'), 'sortable' => true],
            ['key' => 'created_at', 'label' => __('department.created_at'), 'sortable' => true],
            ['key' => 'actions', 'label' => ''],
        ];
    }

    /**
     * Base query for departments.
     */
    protected function query(): Builder
    {
        return Department::query()->with('school');
    }

    /**
     * Search implementation.
     */
    protected function applySearch(Builder $query): Builder
    {
        return $query->where('name', 'like', "%{$this->search}%");
    }

    // --- Record Actions ---

    public function create(): void
    {
        $this->resetErrorBag();
        $this->formData = [
            'id' => null,
            'name' => '',
            'description' => '',
        ];
        $this->showModal = true;
    }

    public function edit(Department $department): void
    {
        $this->resetErrorBag();
        $this->formData = [
            'id' => $department->id,
            'name' => $department->name,
            'description' => $department->description ?? '',
        ];
        $this->showModal = true;
    }

    public function save(CreateDepartmentAction $create, UpdateDepartmentAction $update): void
    {
        $this->validate([
            'formData.name' => [
                'required',
                'string',
                'max:255',
                'unique:departments,name,'.($this->formData['id'] ?? 'NULL'),
            ],
            'formData.description' => ['nullable', 'string', 'max:1000'],
        ]);

        if ($this->formData['id']) {
            $department = Department::findOrFail($this->formData['id']);
            $update->execute($department, $this->formData);
            $this->success(__('department.save_success_updated'));
        } else {
            $school = School::firstOrFail();
            $create->execute(array_merge($this->formData, ['school_id' => $school->id]));
            $this->success(__('department.save_success_created'));
        }

        $this->showModal = false;
    }

    public function delete(Department $department, DeleteDepartmentAction $deleteAction): void
    {
        $profileCount = $department->profiles()->count();

        if ($profileCount > 0) {
            $this->error(__('department.delete_blocked', ['count' => $profileCount]));

            return;
        }

        $deleteAction->execute($department);
        $this->success(__('department.delete_success'));
    }

    // --- Bulk Actions ---

    public function deleteSelected(DeleteDepartmentAction $deleteAction): void
    {
        $this->performBulkAction('Delete', function ($id) use ($deleteAction) {
            $department = Department::find($id);
            if ($department && $department->profiles()->doesntExist()) {
                $deleteAction->execute($department);
            }
        });
    }

    public function stats(): array
    {
        return [
            'total' => Department::count(),
            'with_internships' => Department::whereHas('profiles')->count(),
        ];
    }

    #[Layout('layouts::app')]
    public function render()
    {
        return view('livewire.admin.department.department-index', [
            'stats' => $this->stats(),
        ]);
    }
}
