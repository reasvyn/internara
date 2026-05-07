<?php

declare(strict_types=1);

namespace App\Livewire\Teacher;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class AssessInternship extends Component
{
    public function mount(): void
    {
        Gate::authorize('assessInternship', User::class);
    }

    public function render(): View
    {
        return view('livewire.teacher.assess-internship');
    }
}
