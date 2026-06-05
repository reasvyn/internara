<?php

declare(strict_types=1);

namespace App\Domain\User\Aggregates\Dashboard\Livewire;

use App\Domain\Core\Models\ActivityLog;
use App\Domain\User\Models\User;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('core::layouts.app')]
class UserDashboard extends Component
{
    public function getUser(): ?User
    {
        return auth()->user();
    }

    public function getRecentActivities(): Collection
    {
        return ActivityLog::causedBy(auth()->user())
            ->latest()
            ->take(5)
            ->get();
    }

    public function render(): View
    {
        return view('user.dashboard.index');
    }
}
