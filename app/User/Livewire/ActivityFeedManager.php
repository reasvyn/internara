<?php

declare(strict_types=1);

namespace App\User\Livewire;

use App\User\Actions\GetActivityLogsAction;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class ActivityFeedManager extends Component
{
    use WithPagination;

    public function render(): View
    {
        $activities = app(GetActivityLogsAction::class)->execute(
            userId: auth()->id(),
        );

        return view('user.activity-feed', [
            'activities' => $activities,
        ]);
    }
}
