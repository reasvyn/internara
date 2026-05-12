<?php

declare(strict_types=1);

namespace App\Livewire\Schedule;

use App\Actions\Schedule\CreateScheduleAction;
use App\Actions\Schedule\DeleteScheduleAction;
use App\Actions\Schedule\UpdateScheduleAction;
use App\Models\Schedule;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;

class ScheduleIndex extends Component
{
    use WithPagination;

    public bool $showCreateModal = false;

    public bool $showEditModal = false;

    public string $title = '';

    public string $description = '';

    public string $start_at = '';

    public string $end_at = '';

    public string $type = 'meeting';

    public string $location = '';

    public string $internship_id = '';

    public ?Schedule $editingSchedule = null;

    public function resetForm(): void
    {
        $this->title = '';
        $this->description = '';
        $this->start_at = '';
        $this->end_at = '';
        $this->type = 'meeting';
        $this->location = '';
        $this->internship_id = '';
        $this->resetErrorBag();
    }

    public function store(CreateScheduleAction $action): void
    {
        Gate::authorize('create', Schedule::class);

        $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'start_at' => ['required', 'date'],
            'end_at' => ['required', 'date', 'after:start_at'],
            'type' => ['required', 'string'],
            'description' => ['nullable', 'string'],
            'location' => ['nullable', 'string'],
            'internship_id' => ['nullable', 'string'],
        ]);

        $action->execute(auth()->user(), [
            'title' => $this->title,
            'description' => $this->description,
            'start_at' => $this->start_at,
            'end_at' => $this->end_at,
            'type' => $this->type,
            'location' => $this->location,
            'internship_id' => $this->internship_id,
        ]);

        $this->showCreateModal = false;
        $this->resetForm();
        flash()->success('Schedule created successfully.');
    }

    public function edit(Schedule $schedule): void
    {
        $this->editingSchedule = $schedule;
        $this->title = $schedule->title;
        $this->description = $schedule->description ?? '';
        $this->start_at = $schedule->start_at->format('Y-m-d H:i');
        $this->end_at = $schedule->end_at->format('Y-m-d H:i');
        $this->type = $schedule->type;
        $this->location = $schedule->location ?? '';
        $this->internship_id = $schedule->internship_id ?? '';
        $this->showEditModal = true;
    }

    public function update(UpdateScheduleAction $action): void
    {
        Gate::authorize('update', $this->editingSchedule);

        $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'start_at' => ['required', 'date'],
            'end_at' => ['required', 'date', 'after:start_at'],
            'type' => ['required', 'string'],
            'description' => ['nullable', 'string'],
            'location' => ['nullable', 'string'],
            'internship_id' => ['nullable', 'string'],
        ]);

        $action->execute(auth()->user(), $this->editingSchedule, [
            'title' => $this->title,
            'description' => $this->description,
            'start_at' => $this->start_at,
            'end_at' => $this->end_at,
            'type' => $this->type,
            'location' => $this->location,
            'internship_id' => $this->internship_id,
        ]);

        $this->showEditModal = false;
        $this->resetForm();
        $this->editingSchedule = null;
        flash()->success('Schedule updated successfully.');
    }

    public function destroy(Schedule $schedule, DeleteScheduleAction $action): void
    {
        Gate::authorize('delete', $schedule);

        $action->execute(auth()->user(), $schedule);
        flash()->success('Schedule deleted successfully.');
    }

    public function render(): View
    {
        Gate::authorize('viewAny', Schedule::class);

        $schedules = Schedule::with('creator')->latest('start_at')->paginate(20);

        return view('livewire.admin.schedules.index', [
            'schedules' => $schedules,
        ]);
    }
}
