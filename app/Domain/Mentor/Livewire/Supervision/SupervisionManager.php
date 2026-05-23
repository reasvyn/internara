<?php

declare(strict_types=1);

namespace App\Domain\Mentor\Livewire\Supervision;

use App\Domain\Mentor\Models\SupervisionLog;
use App\Domain\Registration\Models\Registration;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

class SupervisionManager extends Component
{
    use WithPagination;

    public ?Registration $registration = null;

    public function mount(): void
    {
        $this->registration = auth()->user()->registrations()->where('status', 'active')->first();
    }

    #[Layout('shared::layouts.app')]
    public function render(): View
    {
        $logs = SupervisionLog::query()
            ->where('registration_id', $this->registration?->id)
            ->with(['supervisor'])
            ->latest('date')
            ->paginate(10);

        return view('mentor.supervision.manager', [
            'logs' => $logs,
        ]);
    }
}
