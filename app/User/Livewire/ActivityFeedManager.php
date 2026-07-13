<?php

declare(strict_types=1);

namespace App\User\Livewire;

use App\User\Actions\ReadActivityLogAction;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class ActivityFeedManager extends Component
{
    use WithPagination;

    public function render(): View
    {
        $userId = auth()->id();

        if ($userId === null) {
            return view('user.activity-feed', [
                'activities' => new LengthAwarePaginator(
                    collect(),
                    0,
                    15,
                ),
            ]);
        }

        $activities = app(ReadActivityLogAction::class)->execute(userId: (string) $userId);

        return view('user.activity-feed', [
            'activities' => $activities,
        ]);
    }
}
