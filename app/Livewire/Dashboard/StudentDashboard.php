<?php

declare(strict_types=1);

namespace App\Livewire\Dashboard;

use App\Models\Logbook;
use App\Models\Registration;
use Livewire\Attributes\Layout;
use Livewire\Component;

class Dashboard extends Component
{
    public ?Registration $registration = null;

    public int $totalJournals = 0;

    public int $verifiedJournals = 0;

    public function mount(): void
    {
        $user = auth()->user();
        $this->registration = $user
            ->registrations()
            ->with(['placement.company', 'internship'])
            ->where('status', 'active')
            ->first();

        if ($this->registration) {
            $this->totalJournals = Logbook::where('user_id', $user->id)
                ->where('registration_id', $this->registration->id)
                ->count();

            $this->verifiedJournals = Logbook::where('user_id', $user->id)
                ->where('registration_id', $this->registration->id)
                ->where('is_verified', true)
                ->count();
        }
    }

    #[Layout('layouts::app')]
    public function render()
    {
        return view('livewire.dashboard.student');
    }
}
