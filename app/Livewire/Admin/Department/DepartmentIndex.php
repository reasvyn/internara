<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Department;

use App\Actions\Department\CreateDepartmentAction;
use App\Actions\Department\DeleteDepartmentAction;
use App\Actions\Department\UpdateDepartmentAction;
use App\Models\Department;
use App\Models\School;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

class DepartmentIndex extends Component
{
    use WithPagination;

    public bool $showModal = false;
    public string $departmentId = '';
    public string $name = '';
    public string $description = '';

    public string $search = '';

    protected $queryString = ['search'];

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'unique:departments,name,' . $this->departmentId],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function create(): void
    {
        $this->reset(['departmentId', 'name', 'description']);
        $this->showModal = true;
    }

    public function edit(Department $department): void
    {
        $this->departmentId = $department->id;
        $this->name = $department->name;
        $this->description = $department->description ?? '';
        $this->showModal = true;
    }

    public function save(CreateDepartmentAction $create, UpdateDepartmentAction $update): void
    {
        $validated = $this->validate();

        if ($this->departmentId) {
            $department = Department::findOrFail($this->departmentId);
            $update->execute($department, $validated);
            flash()->success(__('department.save_success_updated'));
        } else {
            $school = School::firstOrFail();
            $create->execute(array_merge($validated, ['school_id' => $school->id]));
            flash()->success(__('department.save_success_created'));
        }

        $this->showModal = false;
        $this->reset(['departmentId', 'name', 'description']);
    }

    public function delete(Department $department, DeleteDepartmentAction $deleteAction): void
    {
        $profileCount = $department->profiles()->count();

        if ($profileCount > 0) {
            flash()->error(__('department.delete_blocked', ['count' => $profileCount]));

            return;
        }

        $deleteAction->execute($department);
        flash()->success(__('department.delete_success'));
    }

    public function stats(): array
    {
        return [
            'total' => Department::count(),
            'with_internships' => Department::whereHas('profiles')->count(),
        ];
    }

    #[Layout('components.layouts.app')]
    public function render()
    {
        $departments = Department::query()
            ->with('school')
            ->when($this->search, fn($q) => $q->where('name', 'like', "%{$this->search}%"))
            ->orderBy('name')
            ->paginate(10);

        return view('livewire.admin.department.department-index', [
            'departments' => $departments,
            'stats' => $this->stats(),
        ]);
    }
}
