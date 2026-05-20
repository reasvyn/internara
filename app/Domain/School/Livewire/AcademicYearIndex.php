<?php

declare(strict_types=1);

namespace App\Domain\School\Livewire;

use App\Domain\Core\Exceptions\RejectedException;
use App\Domain\School\Actions\ActivateAcademicYearAction;
use App\Domain\School\Actions\BulkDeleteAcademicYearsAction;
use App\Domain\School\Actions\CreateAcademicYearAction;
use App\Domain\School\Actions\DeleteAcademicYearAction;
use App\Domain\School\Models\AcademicYear;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class AcademicYearIndex extends Component
{
    use WithPagination;

    public bool $showModal = false;

    public bool $showConfirm = false;

    public string $confirmMessage = '';

    public string $confirmType = '';

    public ?string $confirmTarget = null;

    public array $formData = [
        'name' => '',
        'start_date' => '',
        'end_date' => '',
    ];

    #[Url(as: 'q', history: true)]
    public string $search = '';

    public array $selectedIds = [];

    public function boot(): void
    {
        abort_unless(auth()->user()->hasAnyRole(['super_admin', 'admin']), 403);
    }

    public function resetForm(): void
    {
        $this->formData = [
            'name' => '',
            'start_date' => '',
            'end_date' => '',
        ];

        $this->resetErrorBag();
    }

    public function store(CreateAcademicYearAction $action): void
    {
        $this->validate([
            'formData.name' => ['required', 'string', 'max:50', 'unique:academic_years,name'],
            'formData.start_date' => ['required', 'date'],
            'formData.end_date' => ['required', 'date', 'after:formData.start_date'],
        ]);

        $action->execute([
            'name' => $this->formData['name'],
            'start_date' => $this->formData['start_date'],
            'end_date' => $this->formData['end_date'],
            'is_active' => false,
        ]);

        $this->showModal = false;
        $this->resetForm();
        flash()->success(__('academic_year.created'));
    }

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

    public function confirmAction(
        ActivateAcademicYearAction $activateAction,
        DeleteAcademicYearAction $deleteAction,
    ): void {
        if ($this->confirmTarget === null) {
            return;
        }

        $year = AcademicYear::findOrFail($this->confirmTarget);

        match ($this->confirmType) {
            'activate' => $this->executeActivate($year, $activateAction),
            'delete' => $this->executeDelete($year, $deleteAction),
            default => null,
        };

        $this->showConfirm = false;
        $this->confirmTarget = null;
        $this->confirmType = '';
    }

    private function executeActivate(AcademicYear $year, ActivateAcademicYearAction $action): void
    {
        try {
            $action->execute($year);
            flash()->success(__('academic_year.activated'));
        } catch (RejectedException $e) {
            flash()->error($e->getMessage());
        }
    }

    private function executeDelete(AcademicYear $year, DeleteAcademicYearAction $action): void
    {
        try {
            $action->execute($year);
            flash()->success(__('academic_year.deleted'));
        } catch (RejectedException $e) {
            flash()->error($e->getMessage());
        }
    }

    public function clearSelection(): void
    {
        $this->selectedIds = [];
    }

    public function toggleSelectAll(): void
    {
        $ids = $this->yearsQuery()->paginate(10)->pluck('id')->toArray();

        if (count($this->selectedIds) === count($ids)) {
            $this->selectedIds = [];
        } else {
            $this->selectedIds = $ids;
        }
    }

    public function deleteSelected(BulkDeleteAcademicYearsAction $action): void
    {
        if ($this->selectedIds === []) {
            return;
        }

        try {
            $count = $action->execute($this->selectedIds);

            $this->clearSelection();
            flash()->success(__('academic_year.deleted_selected', ['count' => $count]));
        } catch (\RuntimeException $e) {
            flash()->error($e->getMessage());
        }
    }

    #[Layout('layouts::app')]
    public function render(): View
    {
        return view('school.academic-year-index', [
            'years' => $this->yearsQuery()->paginate(10),
        ]);
    }

    private function yearsQuery()
    {
        return AcademicYear::when($this->search, fn ($q) => $q->where('name', 'like', '%'.$this->search.'%'))
            ->orderByDesc('is_active')
            ->orderBy('name');
    }
}
