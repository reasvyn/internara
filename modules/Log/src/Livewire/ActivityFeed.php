<?php

declare(strict_types=1);

namespace Modules\Log\Livewire;

use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

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
        $filters = array_filter([
            'log_name' => $this->logName,
            'causer_id' => $this->causerId,
        ]);

        $activities = app(\Modules\Log\Services\Contracts\ActivityService::class)
            ->query($filters)
            ->with(['causer', 'subject'])
            ->latest()
            ->paginate(10);

        return view('log::livewire.activity-feed', [
            'activities' => $activities,
        ]);
    }
}
