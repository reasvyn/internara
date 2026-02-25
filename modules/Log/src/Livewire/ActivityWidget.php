<?php

declare(strict_types=1);

namespace Modules\Log\Livewire;

use Livewire\Component;

/**
 * Class ActivityWidget
 *
 * Simplified activity feed designed to be embedded as a dashboard widget.
 */
class ActivityWidget extends Component
{
    /**
     * Render the widget.
     */
    public function render()
    {
        $activities = app(\Modules\Log\Services\Contracts\ActivityService::class)
            ->query()
            ->with(['causer'])
            ->latest()
            ->limit(5)
            ->get();

        return view('log::livewire.activity-widget', [
            'activities' => $activities,
        ]);
    }
}
