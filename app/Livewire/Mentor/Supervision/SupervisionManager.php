<?php

declare(strict_types=1);

namespace App\Livewire\Mentor\Supervision;

use App\Domain\Internship\Models\Registration;
use App\Domain\Mentor\Models\SupervisionLog;
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

    #[Layout('layouts::app')]
    public function render()
    {
        $logs = SupervisionLog::query()
            ->where('registration_id', $this->registration?->id)
            ->with(['supervisor'])
            ->latest('date')
            ->paginate(10);

        return view('livewire.mentor.supervision-manager', [
            'logs' => $logs,
        ]);
    }
}
