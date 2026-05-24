<?php

declare(strict_types=1);

namespace App\Domain\School\Livewire;

use App\Domain\Core\Exceptions\RejectedException;
use App\Domain\Core\Livewire\BaseRecordManager;
use App\Domain\School\Actions\ActivateAcademicYearAction;
use App\Domain\School\Actions\CreateAcademicYearAction;
use App\Domain\School\Actions\DeleteAcademicYearAction;
use App\Domain\School\Actions\UpdateAcademicYearAction;
use App\Domain\School\Livewire\Forms\AcademicYearForm;
use App\Domain\School\Models\AcademicYear;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;

class AcademicYearManager extends BaseRecordManager
{
    public bool $showModal = false;

    public bool $showConfirm = false;

    public string $confirmMessage = '';

    public string $confirmType = '';

    public ?string $confirmTarget = null;

    public AcademicYearForm $form;

    public function boot(): void
    {
        $this->authorize('viewAny', AcademicYear::class);
    }

    public function headers(): array
    {
        return [
            ['key' => 'name', 'label' => __('academic_year.name'), 'sortable' => true],
            ['key' => 'start_date', 'label' => __('academic_year.start_date'), 'sortable' => true],
            ['key' => 'end_date', 'label' => __('academic_year.end_date'), 'sortable' => true],
            ['key' => 'is_active', 'label' => __('academic_year.status'), 'sortable' => true],
            ['key' => 'actions', 'label' => '', 'sortable' => false],
        ];
    }

    protected function query(): Builder
    {
        return AcademicYear::query();
    }

    protected function applySearch(Builder $query): Builder
    {
        return $query->where('name', 'like', '%'.$this->search.'%');
    }

    protected function applySorting(Builder $query): Builder
    {
        $column = $this->sortBy['column'] ?? 'name';
        $direction = $this->sortBy['direction'] ?? 'asc';

        if ($column === 'is_active') {
            return $query->orderBy('is_active', 'desc')
                ->orderBy('name', $direction);
        }

        return $query->orderBy($column, $direction);
    }

    protected function perPage(): int
    {
        return 10;
    }

    // --- CRUD ---

    public function create(): void
    {
        $this->resetErrorBag();
        $this->form->reset();
        $this->form->id = null;
        $this->showModal = true;
    }

    public function edit(string $id): void
    {
        $year = AcademicYear::findOrFail($id);

        $this->resetErrorBag();
        $this->form->id = $year->id;
        $this->form->name = $year->name;
        $this->form->start_date = $year->start_date->format('Y-m-d');
        $this->form->end_date = $year->end_date->format('Y-m-d');
        $this->showModal = true;
    }

    public function store(CreateAcademicYearAction $action): void
    {
        $this->form->validate();

        $action->execute($this->form->toArray());

        $this->showModal = false;
        $this->form->reset();
        flash()->success(__('academic_year.created'));
    }

    public function update(UpdateAcademicYearAction $action): void
    {
        $this->form->validate();

        $year = AcademicYear::findOrFail($this->form->id);
        $action->execute($year, $this->form->toArray());

        $this->showModal = false;
        $this->form->reset();
        flash()->success(__('academic_year.updated'));
    }

    // --- Confirm Dialogs ---

    public function askActivate(string $id): void
    {
        $year = AcademicYear::findOrFail($id);

        $this->confirmTarget = $id;
        $this->confirmType = 'activate';
        $this->confirmMessage = __('academic_year.confirm_activate', ['name' => $year->name]);
        $this->showConfirm = true;
    }

    public function askDestroy(string $id): void
    {
        $year = AcademicYear::findOrFail($id);

        $this->confirmTarget = $id;
        $this->confirmType = 'delete';
        $this->confirmMessage = __('academic_year.confirm_delete', ['name' => $year->name]);
        $this->showConfirm = true;
    }

    public function askDeleteSelected(): void
    {
        if ($this->selectedIds === []) {
            return;
        }

        $this->confirmTarget = null;
        $this->confirmType = 'delete_selected';
        $this->confirmMessage = __('academic_year.confirm_delete_selected');
        $this->showConfirm = true;
    }

    public function confirmAction(
        ActivateAcademicYearAction $activateAction,
        DeleteAcademicYearAction $deleteAction,
    ): void {
        if ($this->confirmTarget === null && $this->confirmType !== 'delete_selected') {
            return;
        }

        try {
            match ($this->confirmType) {
                'activate' => $this->executeActivate($this->confirmTarget, $activateAction),
                'delete' => $this->executeDelete($this->confirmTarget, $deleteAction),
                'delete_selected' => $this->executeDeleteSelected($deleteAction),
                default => null,
            };
        } catch (RejectedException $e) {
            flash()->error($e->getMessage());
        }

        $this->showConfirm = false;
        $this->confirmTarget = null;
        $this->confirmType = '';
    }

    private function executeActivate(string $id, ActivateAcademicYearAction $action): void
    {
        $year = AcademicYear::findOrFail($id);
        $action->execute($year);
        flash()->success(__('academic_year.activated'));
    }

    private function executeDelete(string $id, DeleteAcademicYearAction $action): void
    {
        $year = AcademicYear::findOrFail($id);
        $action->execute($year);
        flash()->success(__('academic_year.deleted'));
    }

    private function executeDeleteSelected(DeleteAcademicYearAction $action): void
    {
        $this->performBulkAction('Delete', function ($id) use ($action) {
            $year = AcademicYear::find($id);
            if ($year && $year->asAcademicYearState()->canBeDeleted()) {
                $action->execute($year);
            }
        });
    }

    public function render(): View
    {
        return view('school.academic-year-manager', [
            'years' => $this->rows(),
        ]);
    }
}
