<?php

declare(strict_types=1);

namespace App\Domain\Guidance\Aggregates\Mentor\Livewire;

use App\Domain\User\Models\User;
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
        return view('guidance.mentor.assess-internship');
    }
}
