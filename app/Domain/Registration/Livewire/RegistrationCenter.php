<?php

declare(strict_types=1);

namespace App\Domain\Registration\Livewire;

use App\Domain\Internship\Models\Internship;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

class RegistrationCenter extends Component
{
    #[Computed]
    public function openInternships(): Collection
    {
        return Internship::query()
            ->whereIn('status', ['published', 'active'])
            ->where(function ($q) {
                $q->whereNull('registration_start_date')
                    ->orWhere('registration_start_date', '<=', now()->toDateString());
            })
            ->where(function ($q) {
                $q->whereNull('registration_end_date')
                    ->orWhere('registration_end_date', '>=', now()->toDateString());
            })
            ->orderBy('registration_end_date')
            ->get();
    }

    public function render(): View
    {
        return view('registration.registration-center');
    }
}
