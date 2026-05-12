<?php

declare(strict_types=1);

namespace App\Livewire\Dashboard;

use Livewire\Attributes\Layout;
use Livewire\Component;

class SupervisorDashboard extends Component
{
    public function boot(): void
    {
        abort_unless(auth()->user()->hasRole('supervisor'), 403);
    }

    #[Layout('layouts::app')]
    public function render()
    {
        return view('livewire.dashboard.supervisor');
    }
}
