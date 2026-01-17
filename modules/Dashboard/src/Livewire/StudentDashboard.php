<?php

declare(strict_types=1);

namespace Modules\Dashboard\Livewire;

use Illuminate\View\View;
use Livewire\Component;

class StudentDashboard extends Component
{
    public function render(): View
    {
        return view('dashboard::livewire.student-dashboard')->layout(
            'dashboard::components.layouts.dashboard',
            [
                'title' => 'Dasbor Siswa',
            ],
        );
    }
}
