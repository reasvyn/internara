<?php

declare(strict_types=1);

namespace App\Enrollment\Livewire;

use App\Enrollment\Models\Registration;
use App\Program\Internship\Models\Internship;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Computed;
use Livewire\Component;

class RegistrationCenter extends Component
{
    use AuthorizesRequests;

    public function boot(): void
    {
        $this->authorize('viewAny', Registration::class);
    }

    #[Computed]
    public function openInternships(): Collection
    {
        return Internship::query()
            ->whereIn('status', ['published', 'active'])
            ->where(function ($q) {
                $q->whereNull('registration_start_date')->orWhere(
                    'registration_start_date',
                    '<=',
                    now()->toDateString(),
                );
            })
            ->where(function ($q) {
                $q->whereNull('registration_end_date')->orWhere(
                    'registration_end_date',
                    '>=',
                    now()->toDateString(),
                );
            })
            ->orderBy('registration_end_date')
            ->get();
    }

    public function render(): View
    {
        return view('enrollment.registration-center');
    }
}
