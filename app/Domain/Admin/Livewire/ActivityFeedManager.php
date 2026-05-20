<?php

declare(strict_types=1);

namespace App\Domain\Admin\Livewire;

use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class ActivityFeedManager extends Component
{
    use WithPagination;

    public function render(): View
    {
        $activities = auth()->user()->activityLogs()->latest()->paginate(50);

        return view('admin.activity-feed', [
            'activities' => $activities,
        ]);
    }
}
