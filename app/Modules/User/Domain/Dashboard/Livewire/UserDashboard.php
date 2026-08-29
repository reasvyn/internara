<?php

declare(strict_types=1);

namespace App\Modules\User\Domain\Dashboard\Livewire;

use App\Modules\Core\Models\ActivityLog;
use App\Modules\User\Models\User;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('ui::layouts.app')]
class UserDashboard extends Component
{
    public function getUser(): ?User
    {
        return auth()->user();
    }

    public function getRecentActivities(): Collection
    {
        return ActivityLog::causedBy(auth()->user())->latest()->take(5)->get();
    }

    public function render(): View
    {
        return view('user.dashboard.index');
    }
}
