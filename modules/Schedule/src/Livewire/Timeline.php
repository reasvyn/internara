<?php

declare(strict_types=1);

namespace Modules\Schedule\Livewire;

use Illuminate\Support\Collection;
use Illuminate\View\View;
use Livewire\Component;
use Modules\Schedule\Services\Contracts\ScheduleService;

class Timeline extends Component
{
    /**
     * The timeline events.
     */
    public Collection $schedules;

    /**
     * Initialize the component.
     */
    public function mount(ScheduleService $service): void
    {
        // For student view, we'll just get the general timeline for now.
        // In the future, this would be scoped to the student's program.
        $this->schedules = $service->getStudentTimeline(auth()->id() ?: '');
    }

    /**
     * Render the component view.
     */
    public function render(): View
    {
        return view('schedule::livewire.timeline');
    }
}
