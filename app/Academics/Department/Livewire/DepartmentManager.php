<?php

declare(strict_types=1);

namespace App\Academics\Department\Livewire;

use App\Academics\Department\Actions\CreateDepartmentAction;
use App\Academics\Department\Actions\DeleteDepartmentAction;
use App\Academics\Department\Actions\UpdateDepartmentAction;
use App\Academics\Department\Livewire\Forms\DepartmentForm;
use App\Academics\Department\Models\Department;
use App\Core\Enums\CsvRowResult;
use App\Core\Exceptions\RejectedException;
use App\Core\Livewire\BaseRecordManager;
use App\Core\Support\CsvHandler;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Computed;
use Livewire\WithFileUploads;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DepartmentManager extends BaseRecordManager
{
    use WithFileUploads;

    public bool $showModal = false;

    public bool $showConfirm = false;

    public string $confirmMessage = '';

    public string $confirmType = '';

    public ?string $confirmTarget = null;

    public $importFile;

    public DepartmentForm $form;

    public function boot(): void
    {
        $this->authorize('viewAny', Department::class);
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
        return Department::query();
    }

    protected function applySearch(Builder $query): Builder
    {
        return $query->where('name', 'like', "%{$this->search}%");
    }

    // --- Record Actions ---

    public function create(): void
    {
        $this->authorize('create', Department::class);

        $this->resetErrorBag();
        $this->form->reset();
        $this->form->id = null;
        $this->showModal = true;
    }

    public function edit(string $id): void
    {
        $department = Department::findOrFail($id);
        $this->authorize('update', $department);

        $this->resetErrorBag();
        $this->form->id = $department->id;
        $this->form->name = $department->name;
        $this->form->description = $department->description ?? '';
        $this->showModal = true;
    }

    public function save(CreateDepartmentAction $create, UpdateDepartmentAction $update): void
    {
        $this->form->validate();

        if ($this->form->id) {
            $department = Department::findOrFail($this->form->id);
            $this->authorize('update', $department);
            $update->execute($department, $this->form->toArray());
            flash()->success(__('department.save_success_updated'));
        } else {
            $this->authorize('create', Department::class);
            $create->execute($this->form->toArray());
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
        } catch (\RuntimeException $e) {
            flash()->error($e->getMessage());
        }

        $this->showConfirm = false;
        $this->confirmTarget = null;
        $this->confirmType = '';
    }

    private function executeDelete(string $id, DeleteDepartmentAction $action): void
    {
        $department = Department::findOrFail($id);
        $this->authorize('delete', $department);

        if (! $department->asDepartmentState()->canBeDeleted()) {
            flash()->error(
                __('department.delete_blocked', ['count' => $department->profiles()->count()]),
            );

            return;
        }

        $action->execute($department);
        flash()->success(__('department.delete_success'));
    }

    private function executeDeleteSelected(DeleteDepartmentAction $action): void
    {
        $deleted = 0;
        $blocked = 0;

        foreach ($this->selectedIds as $id) {
            $department = Department::find($id);
            if (! $department) {
                continue;
            }
            if (! $department->asDepartmentState()->canBeDeleted()) {
                $blocked++;

                continue;
            }
            try {
                $action->execute($department);
                $deleted++;
            } catch (RejectedException) {
                $blocked++;
            }
        }

        if ($deleted > 0) {
            flash()->success(
                trans_choice('department.delete_success_bulk', $deleted, ['count' => $deleted]),
            );
        }
        if ($blocked > 0) {
            flash()->error(
                trans_choice('department.delete_blocked_bulk', $blocked, ['count' => $blocked]),
            );
        }

        $this->clearSelection();
    }

    // --- Import / Export / Template ---

    public function updatedImportFile(): void
    {
        if ($this->importFile) {
            $this->import(app(CsvHandler::class), app(CreateDepartmentAction::class));
        }
    }

    public function import(CsvHandler $csv, CreateDepartmentAction $create): void
    {
        $this->authorize('create', Department::class);

        $this->validate([
            'importFile' => ['required', 'file', 'mimes:csv,txt', 'max:2048'],
        ]);

        $result = $csv->import($this->importFile->getRealPath(), function (array $row) use (
            $create,
        ) {
            $name = trim($row[0] ?? '');

            if ($name === '') {
                return null;
            }

            if (Department::where('name', $name)->exists()) {
                return CsvRowResult::SKIPPED;
            }

            $create->execute([
                'name' => $name,
                'description' => trim($row[1] ?? '') ?: null,
            ]);

            return CsvRowResult::CREATED;
        });

        $this->importFile = null;

        if ($result['invalid']) {
            flash()->error(__('department.import_invalid'));

            return;
        }

        flash()->success(
            __('department.import_summary', [
                'created' => $result['created'],
                'skipped' => $result['skipped'],
            ]),
        );
    }

    public function export(CsvHandler $csv): StreamedResponse
    {
        $this->authorize('viewAny', Department::class);

        $departments = Department::query()
            ->when($this->search, fn ($q) => $q->where('name', 'like', "%{$this->search}%"))
            ->orderBy('name')
            ->get();

        return $csv->export(
            $departments,
            ['name', 'description'],
            fn ($d) => [$d->name, $d->description ?? ''],
            'departments.csv',
        );
    }

    public function exportSelected(CsvHandler $csv): ?StreamedResponse
    {
        if ($this->selectedIds === []) {
            flash()->warning(__('common.actions.no_records_selected'));

            return null;
        }

        $departments = Department::whereIn('id', $this->selectedIds)->orderBy('name')->get();

        return $csv->export(
            $departments,
            ['name', 'description'],
            fn ($d) => [$d->name, $d->description ?? ''],
            'departments-selected.csv',
        );
    }

    public function downloadTemplate(CsvHandler $csv): StreamedResponse
    {
        return $csv->downloadTemplate(
            ['name', 'description'],
            [__('department.template_example_name'), __('department.template_example_description')],
            'departments-template.csv',
        );
    }

    #[Computed]
    public function stats(): array
    {
        return [
            'total' => Department::count(),
            'with_internships' => Department::whereHas('profiles')->count(),
        ];
    }

    public function render(): View
    {
        return view('academics.department.department-manager', [
            'stats' => $this->stats,
        ]);
    }
}
