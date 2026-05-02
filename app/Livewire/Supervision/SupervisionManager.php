<?php

declare(strict_types=1);

namespace App\Livewire\Supervision;

use App\Models\InternshipRegistration;
use App\Models\SupervisionLog;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

class SupervisionManager extends Component
{
    use WithPagination;

    public ?InternshipRegistration $registration = null;

    public function mount(): void
    {
        $this->registration = auth()->user()->registrations()->where('status', 'active')->first();
    }

    #[Layout('components.layouts.app')]
    public function render()
    {
        $logs = SupervisionLog::query()
            ->where('registration_id', $this->registration?->id)
            ->with(['supervisor'])
            ->latest('date')
            ->paginate(10);

        return view('livewire.supervision.supervision-manager', [
            'logs' => $logs,
        ]);
    }
}
