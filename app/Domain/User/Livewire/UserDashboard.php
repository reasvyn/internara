<?php

declare(strict_types=1);

namespace App\Domain\User\Livewire;

use App\Domain\Core\Models\ActivityLog;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

class UserDashboard extends Component
{
    public function recentActivities(): Collection
    {
        return ActivityLog::causedBy(auth()->user())
            ->latest()
            ->take(5)
            ->get();
    }

    #[Layout('layouts::app')]
    public function render(): View
    {
        return view('user.dashboard', [
            'activities' => $this->recentActivities(),
        ]);
    }
}
