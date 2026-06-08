<?php

declare(strict_types=1);

namespace App\User\Dashboard\Livewire;

use App\Enrollment\Registration\Models\Registration;
use App\User\Dashboard\Actions\GetStudentDashboardDataAction;
use Illuminate\View\View;

class StudentDashboard extends UserDashboard
{
    public function boot(): void
    {
        abort_unless(auth()->user()?->hasRole('student'), 403);
    }

    public ?Registration $registration = null;

    public int $totalJournals = 0;

    public int $verifiedJournals = 0;

    public float $attendancePercent = 100.0;

    public int $assignmentSubmittedCount = 0;

    public int $assignmentTotalCount = 0;

    public int $handbookReadCount = 0;

    public int $handbookTotalCount = 0;

    public function mount(GetStudentDashboardDataAction $action): void
    {
        $user = auth()->user();

        $data = $action->execute($user->id);

        $this->registration = $data['registration'];
        $this->totalJournals = $data['totalJournals'];
        $this->verifiedJournals = $data['verifiedJournals'];
        $this->attendancePercent = $data['attendancePercent'];
        $this->assignmentSubmittedCount = $data['assignmentSubmittedCount'];
        $this->assignmentTotalCount = $data['assignmentTotalCount'];
        $this->handbookReadCount = $data['handbookReadCount'];
        $this->handbookTotalCount = $data['handbookTotalCount'];
    }

    public function render(): View
    {
        return view('user.dashboard.student');
    }
}
