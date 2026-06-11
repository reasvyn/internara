<?php

declare(strict_types=1);

namespace App\Academics\School\Livewire;

use Illuminate\View\View;
use Livewire\Component;

class SchoolEditor extends Component
{
    public function render(): View
    {
        return view('academics.school.school-editor');
    }
}
