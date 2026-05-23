<?php

declare(strict_types=1);

namespace App\Domain\User\Livewire\Dashboards;

use App\Domain\Registration\Models\Registration;
use App\Domain\User\Actions\GetStudentDashboardDataAction;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

class StudentDashboard extends Component
{
    public function boot(): void
    {
        abort_unless(auth()->user()->hasRole('student'), 403);
    }

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
    public function render(): View
    {
        return view('user.dashboards.student');
    }
}
