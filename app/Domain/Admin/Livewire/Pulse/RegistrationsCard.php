<?php

declare(strict_types=1);

namespace App\Domain\Admin\Livewire\Pulse;

use App\Domain\Registration\Models\Registration;
use Illuminate\View\View;
use Laravel\Pulse\Livewire\Card;
use Livewire\Attributes\Lazy;

#[Lazy]
class RegistrationsCard extends Card
{
    public function render(): View
    {
        return view('admin.pulse.registrations-card', [
            'total' => Registration::count(),
            'pending' => Registration::where('status', 'pending')->count(),
            'active' => Registration::where('status', 'active')->count(),
            'completed' => Registration::where('status', 'completed')->count(),
        ]);
    }
}
