<?php

declare(strict_types=1);

namespace App\Livewire\Dashboard;

use App\Actions\Dashboard\GetStudentDashboardDataAction;
use App\Models\Registration;
use Livewire\Attributes\Layout;
use Livewire\Component;

class StudentDashboard extends Component
{
    public ?Registration $registration = null;

    public int $totalJournals = 0;

    public int $verifiedJournals = 0;

    public function mount(GetStudentDashboardDataAction $action): void
    {
        $user = auth()->user();

        $data = $action->execute($user->id);

        $this->registration = $data['registration'];
        $this->totalJournals = $data['totalJournals'];
        $this->verifiedJournals = $data['verifiedJournals'];
    }

    #[Layout('layouts::app')]
    public function render()
    {
        return view('livewire.dashboard.student');
    }
}
