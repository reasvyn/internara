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
            session()->flash('success', 'Department updated successfully.');
        } else {
            $school = School::firstOrFail();
            $create->execute(array_merge($validated, ['school_id' => $school->id]));
            session()->flash('success', 'Department created successfully.');
        }

        $this->showModal = false;
        $this->reset(['departmentId', 'name', 'description']);
    }

    public function delete(Department $department, DeleteDepartmentAction $deleteAction): void
    {
        $deleteAction->execute($department);
        session()->flash('success', 'Department deleted successfully.');
    }

    #[Layout('components.layouts.app')]
    public function render()
    {
        $departments = Department::query()
            ->when($this->search, fn($q) => $q->where('name', 'like', "%{$this->search}%"))
            ->paginate(10);

        return view('livewire.admin.department.department-index', [
            'departments' => $departments,
        ]);
    }
}
