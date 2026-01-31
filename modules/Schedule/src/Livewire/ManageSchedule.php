<?php

declare(strict_types=1);

namespace Modules\Schedule\Livewire;

use Illuminate\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Modules\Schedule\Models\Schedule;
use Modules\Schedule\Services\Contracts\ScheduleService;

/**
 * Class ManageSchedule
 *
 * Provides the administrative interface for managing internship schedules.
 */
class ManageSchedule extends Component
{
    use WithPagination;

    /**
     * Search query for filtering schedules.
     */
    #[Url(history: true)]
    public string $search = '';

    /**
     * Selected schedule ID for editing.
     */
    public ?string $selectedScheduleId = null;

    /**
     * Whether the schedule form modal is open.
     */
    public bool $showForm = false;

    /**
     * Query parameters for the component.
     */
    protected $queryString = [
        'search' => ['except' => ''],
    ];

    /**
     * Reset pagination when searching.
     */
    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    /**
     * Open the form to create a new schedule.
     */
    public function create(): void
    {
        $this->authorize('create', Schedule::class);

        $this->selectedScheduleId = null;
        $this->showForm = true;
    }

    /**
     * Open the form to edit an existing schedule.
     */
    public function edit(string $id): void
    {
        $this->authorize('update', Schedule::class);

        $this->selectedScheduleId = $id;
        $this->showForm = true;
    }

    /**
     * Delete a schedule.
     */
    public function delete(string $id, ScheduleService $service): void
    {
        $this->authorize('delete', Schedule::class);

        if ($service->delete($id)) {
            $this->dispatch('toast', message: __('schedule::messages.schedule_deleted'), type: 'success');
        }
    }

    /**
     * Handle schedule saved event.
     */
    public function scheduleSaved(): void
    {
        $this->showForm = false;
        $this->dispatch('toast', message: __('schedule::messages.schedule_saved'), type: 'success');
    }

    /**
     * Render the component view.
     */
    public function render(ScheduleService $service): View
    {
        $this->authorize('viewAny', Schedule::class);

        $headers = [
            ['key' => 'title', 'label' => __('schedule::ui.schedule_name')],
            ['key' => 'type', 'label' => __('schedule::ui.type')],
            ['key' => 'start_at', 'label' => __('schedule::ui.start_time')],
            ['key' => 'location', 'label' => __('schedule::ui.location')],
        ];

        $schedules = $service->paginate(
            filters: ['search' => $this->search],
            perPage: 10
        );

        return view('schedule::livewire.manage-schedule', [
            'schedules' => $schedules,
            'headers' => $headers,
        ])->layout('ui::components.layouts.dashboard', [
            'title' => __('schedule::ui.manage_title'),
        ]);
    }
}
