<?php

declare(strict_types=1);

namespace Modules\Log\Livewire;

use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Modules\Log\Models\Activity;

class ActivityFeed extends Component
{
    use WithPagination;

    /**
     * The log name to filter by.
     */
    #[Url]
    public ?string $logName = null;

    /**
     * Filter by a specific causer ID.
     */
    public ?string $causerId = null;

    /**
     * Render the component.
     */
    public function render()
    {
        $activities = Activity::query()
            ->with(['causer', 'subject'])
            ->when($this->logName, fn ($q) => $q->where('log_name', $this->logName))
            ->when($this->causerId, fn ($q) => $q->where('causer_id', $this->causerId))
            ->latest()
            ->paginate(10);

        return view('log::livewire.activity-feed', [
            'activities' => $activities,
        ]);
    }
}
