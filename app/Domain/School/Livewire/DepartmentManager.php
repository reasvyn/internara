<?php

declare(strict_types=1);

namespace App\Domain\School\Livewire;

use App\Domain\Core\Exceptions\RejectedException;
use App\Domain\Core\Livewire\BaseRecordManager;
use App\Domain\School\Actions\CreateDepartmentAction;
use App\Domain\School\Actions\DeleteDepartmentAction;
use App\Domain\School\Actions\UpdateDepartmentAction;
use App\Domain\School\Models\Department;
use App\Domain\School\Models\School;
use App\Domain\Shared\Support\CsvHandler;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\WithFileUploads;

class DepartmentManager extends BaseRecordManager
{
    use WithFileUploads;

    public bool $showModal = false;

    public bool $showConfirm = false;

    public string $confirmMessage = '';

    public string $confirmType = '';

    public ?string $confirmTarget = null;

    public $importFile;

    public array $formData = [
        'id' => null,
        'name' => '',
        'description' => '',
    ];

    public function boot(): void
    {
        if (
            ! auth()
                ->user()
                ?->hasAnyRole(['super_admin', 'admin'])
        ) {
            abort(403, 'Unauthorized access.');
        }
    }

    public function headers(): array
    {
        return [
            ['key' => 'name', 'label' => __('department.name'), 'sortable' => true],
            ['key' => 'description', 'label' => __('department.description'), 'sortable' => true],
            ['key' => 'created_at', 'label' => __('department.created_at'), 'sortable' => true],
            ['key' => 'actions', 'label' => '', 'sortable' => false],
        ];
    }

    protected function query(): Builder
    {
        return Department::query()->with('school');
    }

    protected function applySearch(Builder $query): Builder
    {
        return $query->where('name', 'like', "%{$this->search}%");
    }

    protected function applyFilters(Builder $query): Builder
    {
        return $query;
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
            flash()->success(__('department.save_success_updated'));
        } else {
            $school = School::firstOrFail();
            $create->execute(array_merge($this->formData, ['school_id' => $school->id]));
            flash()->success(__('department.save_success_created'));
        }

        $this->showModal = false;
    }

    // --- Confirm Dialog ---

    public function askDelete(string $id): void
    {
        $department = Department::findOrFail($id);

        $this->confirmTarget = $id;
        $this->confirmType = 'delete';
        $this->confirmMessage = __('department.confirm_delete', ['name' => $department->name]);
        $this->showConfirm = true;
    }

    public function askDeleteSelected(): void
    {
        if ($this->selectedIds === []) {
            return;
        }

        $this->confirmTarget = null;
        $this->confirmType = 'delete_selected';
        $this->confirmMessage = __('department.confirm_delete_selected');
        $this->showConfirm = true;
    }

    public function confirmAction(DeleteDepartmentAction $deleteAction): void
    {
        if ($this->confirmTarget === null && $this->confirmType !== 'delete_selected') {
            return;
        }

        try {
            match ($this->confirmType) {
                'delete' => $this->executeDelete($this->confirmTarget, $deleteAction),
                'delete_selected' => $this->executeDeleteSelected($deleteAction),
                default => null,
            };
        } catch (RejectedException|\RuntimeException $e) {
            flash()->error($e->getMessage());
        }

        $this->showConfirm = false;
        $this->confirmTarget = null;
        $this->confirmType = '';
    }

    private function executeDelete(string $id, DeleteDepartmentAction $action): void
    {
        $department = Department::findOrFail($id);

        if (! $department->asDepartmentState()->canBeDeleted()) {
            flash()->error(__('department.delete_blocked', ['count' => $department->profiles()->count()]));

            return;
        }

        $action->execute($department);
        flash()->success(__('department.delete_success'));
    }

    private function executeDeleteSelected(DeleteDepartmentAction $action): void
    {
        $this->performBulkAction('Delete', function ($id) use ($action) {
            $department = Department::find($id);
            if ($department && $department->asDepartmentState()->canBeDeleted()) {
                $action->execute($department);
            }
        });
    }

    // --- Import / Export / Template ---

    public function updatedImportFile(): void
    {
        if ($this->importFile) {
            $this->import();
        }
    }

    public function import(CsvHandler $csv): void
    {
        $this->validate([
            'importFile' => ['required', 'file', 'mimes:csv,txt', 'max:2048'],
        ]);

        $result = $csv->import($this->importFile->getRealPath(), function (array $row) {
            $name = trim($row[0] ?? '');

            if ($name === '') {
                return null;
            }

            if (Department::where('name', $name)->exists()) {
                return 'skipped';
            }

            Department::create([
                'name' => $name,
                'description' => trim($row[1] ?? '') ?: null,
                'school_id' => School::firstOrFail()->id,
            ]);

            return 'created';
        });

        $this->importFile = null;

        if ($result['invalid']) {
            flash()->error(__('department.import_invalid'));

            return;
        }

        flash()->success(__('department.import_summary', [
            'created' => $result['created'],
            'skipped' => $result['skipped'],
        ]));
    }

    public function export(CsvHandler $csv): mixed
    {
        $departments = Department::query()
            ->when($this->search, fn ($q) => $q->where('name', 'like', "%{$this->search}%"))
            ->orderBy('name')
            ->get();

        return $csv->export($departments, ['name', 'description'],
            fn ($d) => [$d->name, $d->description ?? ''],
            'departments.csv',
        )->send();
    }

    public function exportSelected(CsvHandler $csv): mixed
    {
        if ($this->selectedIds === []) {
            flash()->warning(__('common.actions.no_records_selected'));

            return null;
        }

        $departments = Department::whereIn('id', $this->selectedIds)->orderBy('name')->get();

        return $csv->export($departments, ['name', 'description'],
            fn ($d) => [$d->name, $d->description ?? ''],
            'departments-selected.csv',
        )->send();
    }

    public function downloadTemplate(CsvHandler $csv): mixed
    {
        return $csv->downloadTemplate(
            ['name', 'description'],
            [__('department.template_example_name'), __('department.template_example_description')],
            'departments-template.csv',
        )->send();
    }

    public function stats(): array
    {
        return [
            'total' => Department::count(),
            'with_internships' => Department::whereHas('profiles')->count(),
        ];
    }

    public function render(): View
    {
        return view('school.department-manager', [
            'stats' => $this->stats(),
        ]);
    }
}
