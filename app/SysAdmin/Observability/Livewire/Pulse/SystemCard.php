<?php

declare(strict_types=1);

namespace App\SysAdmin\Observability\Livewire\Pulse;

use App\User\Models\User;
use App\User\Notification\Models\Notification;
use Illuminate\View\View;
use Laravel\Pulse\Livewire\Card;
use Livewire\Attributes\Lazy;

#[Lazy]
class SystemCard extends Card
{
    public function render(): View
    {
        return view('sysadmin.observability.pulse.system-card', [
            'users' => User::count(),
            'unreadNotifications' => Notification::where('is_read', false)->count(),
        ]);
    }
}
