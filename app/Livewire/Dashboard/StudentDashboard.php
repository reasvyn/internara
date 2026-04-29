<?php

declare(strict_types=1);

namespace App\Livewire\Dashboard;

use App\Models\InternshipRegistration;
use App\Models\JournalEntry;
use Livewire\Attributes\Layout;
use Livewire\Component;

class StudentDashboard extends Component
{
    public ?InternshipRegistration $registration = null;
    public int $totalJournals = 0;
    public int $verifiedJournals = 0;

    public function mount(): void
    {
        $user = auth()->user();
        $this->registration = $user->registrations()
            ->with(['placement.company', 'internship'])
            ->where('status', 'active')
            ->first();

        if ($this->registration) {
            $this->totalJournals = JournalEntry::where('user_id', $user->id)
                ->where('registration_id', $this->registration->id)
                ->count();
            
            $this->verifiedJournals = JournalEntry::where('user_id', $user->id)
                ->where('registration_id', $this->registration->id)
                ->where('is_verified', true)
                ->count();
        }
    }

    #[Layout('components.layouts.app')]
    public function render()
    {
        return view('livewire.dashboard.student-dashboard');
    }
}
