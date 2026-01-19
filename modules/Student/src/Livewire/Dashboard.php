<?php

declare(strict_types=1);

namespace Modules\Student\Livewire;

use Illuminate\View\View;
use Livewire\Component;

class Dashboard extends Component
{
    /**
     * Render the student dashboard view.
     */
    public function render(): View
    {
        return view('student::livewire.dashboard')->layout('ui::components.layouts.dashboard', [
            'title' => __('Dasbor Siswa'),
        ]);
    }
}
