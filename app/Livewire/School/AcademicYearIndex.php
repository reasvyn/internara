<?php

declare(strict_types=1);

namespace App\Livewire\School;

use App\Actions\School\ActivateAcademicYearAction;
use App\Actions\School\CreateAcademicYearAction;
use App\Actions\School\DeleteAcademicYearAction;
use App\Models\AcademicYear;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

class AcademicYearIndex extends Component
{
    use WithPagination;

    public bool $showModal = false;

    public string $name = '';

    public string $start_date = '';

    public string $end_date = '';

    public function resetForm(): void
    {
        $this->name = '';
        $this->start_date = '';
        $this->end_date = '';
        $this->resetErrorBag();
    }

    public function store(CreateAcademicYearAction $action): void
    {
        Gate::authorize('create', AcademicYear::class);

        $this->validate([
            'name' => ['required', 'string', 'max:50'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after:start_date'],
        ]);

        $action->execute([
            'name' => $this->name,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'is_active' => false,
        ]);

        $this->showModal = false;
        $this->resetForm();
        $this->dispatch('notify', type: 'success', message: 'Academic year created successfully.');
    }

    public function activate(AcademicYear $year, ActivateAcademicYearAction $action): void
    {
        Gate::authorize('activate', $year);

        $action->execute($year);
        $this->dispatch('notify', type: 'success', message: 'Academic year activated.');
    }

    public function destroy(AcademicYear $year, DeleteAcademicYearAction $action): void
    {
        Gate::authorize('delete', $year);

        $action->execute($year);
        $this->dispatch('notify', type: 'success', message: 'Academic year deleted successfully.');
    }

    #[Layout('layouts::app')]
    public function render()
    {
        $years = AcademicYear::latest('start_date')->paginate(10);

        return view('livewire.school.academic-year-index', [
            'years' => $years,
        ]);
    }
}
