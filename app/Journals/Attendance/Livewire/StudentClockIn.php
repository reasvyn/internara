<?php

declare(strict_types=1);

namespace App\Journals\Attendance\Livewire;

use App\Core\Livewire\BaseFormView;
use App\Journals\Attendance\Actions\ClockInAction;
use App\Journals\Attendance\Actions\ClockOutAction;
use App\Journals\Attendance\Models\Attendance;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;

class StudentClockIn extends BaseFormView
{
    public function clockIn(ClockInAction $action): void
    {
        $this->handleSave(function () use ($action) {
            $action->execute(auth()->user(), []);
            flash()->success('Clocked in successfully.');
        });
    }

    public function clockOut(ClockOutAction $action): void
    {
        $this->handleSave(function () use ($action) {
            $action->execute(auth()->user(), []);
            flash()->success('Clocked out successfully.');
        });
    }

    #[Layout('core::layouts.app')]
    public function render(): View
    {
        $today = Attendance::where('user_id', auth()->id())
            ->whereDate('date', now()->toDateString())
            ->first();

        return view('journals.attendance.student-clock-in', [
            'todayAttendance' => $today,
        ]);
    }
}
