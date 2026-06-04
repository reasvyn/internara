<?php

declare(strict_types=1);

namespace App\Domain\SysAdmin\Livewire\Pulse;

use App\Domain\User\Aggregates\Notification\Models\Notification;
use App\Domain\User\Models\User;
use Illuminate\View\View;
use Laravel\Pulse\Livewire\Card;
use Livewire\Attributes\Lazy;

#[Lazy]
class SystemCard extends Card
{
    public function render(): View
    {
        return view('sysadmin.pulse.system-card', [
            'users' => User::count(),
            'unreadNotifications' => Notification::where('is_read', false)->count(),
        ]);
    }
}
