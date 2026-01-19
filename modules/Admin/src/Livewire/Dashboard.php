<?php

declare(strict_types=1);

namespace Modules\Admin\Livewire;

use Illuminate\View\View;
use Livewire\Component;

class Dashboard extends Component
{
    /**
     * Render the admin dashboard view.
     */
    public function render(): View
    {
        return view('admin::livewire.dashboard')->layout('ui::components.layouts.dashboard', [
            'title' => __('Dasbor Admin'),
        ]);
    }
}
