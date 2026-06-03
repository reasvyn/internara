<?php

declare(strict_types=1);

namespace App\Domain\Certification\Aggregates\Certificate\Livewire;

use App\Domain\Certification\Aggregates\Certificate\Models\Certificate;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

class StudentCertificates extends Component
{
    public function boot(): void
    {
        abort_unless(auth()->user()->hasRole('student'), 403);
    }

    #[Layout('core::layouts.app')]
    public function render(): View
    {
        $certificates = Certificate::query()
            ->whereHas('registration.mentee', fn (Builder $q) => $q->where('user_id', auth()->id()))
            ->where('status', 'issued')
            ->with('template', 'registration.internship')
            ->orderByDesc('issued_at')
            ->get();

        return view('certificate.student-certificates', [
            'certificates' => $certificates,
        ]);
    }
}
