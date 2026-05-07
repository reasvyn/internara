<?php

declare(strict_types=1);

namespace App\Livewire\Mentee;

use App\Models\Assessment\Competency;
use App\Models\Internship\Registration;
use App\Models\Mentee\CompetencyLog;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

class CompetencyLogManager extends Component
{
    use WithPagination;

    public ?Registration $registration = null;

    public bool $showCreateForm = false;

    public ?CompetencyLog $editingLog = null;

    public string $competencyId = '';

    public ?float $score = null;

    public ?string $notes = '';

    public function mount(): void
    {
        $this->registration = Auth::user()
            ->registrations()
            ->where('status', 'active')
            ->first();
    }

    public function create(): void
    {
        $this->resetForm();
        $this->showCreateForm = true;
    }

    public function store(): void
    {
        $this->validate([
            'competencyId' => 'required|exists:competencies,id',
            'score' => 'required|numeric|min:0|max:100',
            'notes' => 'nullable|string|max:1000',
        ]);

        CompetencyLog::create([
            'registration_id' => $this->registration->id,
            'competency_id' => $this->competencyId,
            'evaluator_id' => Auth::id(),
            'score' => $this->score,
            'notes' => $this->notes,
        ]);

        $this->showCreateForm = false;
        $this->resetForm();
        $this->dispatch('notify', type: 'success', message: 'Competency log created successfully.');
    }

    public function edit(CompetencyLog $log): void
    {
        $this->editingLog = $log;
        $this->competencyId = $log->competency_id;
        $this->score = $log->score;
        $this->notes = $log->notes;
        $this->showCreateForm = true;
    }

    public function update(): void
    {
        if (! $this->editingLog) {
            return;
        }

        $this->validate([
            'competencyId' => 'required|exists:competencies,id',
            'score' => 'required|numeric|min:0|max:100',
            'notes' => 'nullable|string|max:1000',
        ]);

        $this->editingLog->update([
            'competency_id' => $this->competencyId,
            'score' => $this->score,
            'notes' => $this->notes,
        ]);

        $this->showCreateForm = false;
        $this->resetForm();
        $this->dispatch('notify', type: 'success', message: 'Competency log updated successfully.');
    }

    public function delete(CompetencyLog $log): void
    {
        $log->delete();
        $this->dispatch('notify', type: 'success', message: 'Competency log deleted successfully.');
    }

    public function cancel(): void
    {
        $this->showCreateForm = false;
        $this->resetForm();
    }

    protected function resetForm(): void
    {
        $this->competencyId = '';
        $this->score = null;
        $this->notes = '';
        $this->editingLog = null;
    }

    #[Layout('layouts::app')]
    public function render()
    {
        $logs = CompetencyLog::query()
            ->where('registration_id', $this->registration?->id)
            ->with(['competency', 'evaluator'])
            ->latest()
            ->paginate(10);

        $competencies = Competency::orderBy('name')->get();

        return view('livewire.mentee.competency-log-manager', [
            'logs' => $logs,
            'competencies' => $competencies,
        ]);
    }
}
