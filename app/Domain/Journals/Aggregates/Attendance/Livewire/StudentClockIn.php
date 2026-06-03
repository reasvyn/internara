<?php

declare(strict_types=1);

namespace App\Domain\Journals\Aggregates\Attendance\Livewire;

use App\Domain\Journals\Aggregates\Attendance\Actions\ClockInAction;
use App\Domain\Journals\Aggregates\Attendance\Actions\ClockOutAction;
use App\Domain\Journals\Aggregates\Attendance\Models\Attendance;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

class StudentClockIn extends Component
{
    public function clockIn(ClockInAction $action): void
    {
        try {
            $action->execute(auth()->user(), []);
            flash()->success('Clocked in successfully.');
        } catch (\Exception $e) {
            flash()->error($e->getMessage());
        }
    }

    public function clockOut(ClockOutAction $action): void
    {
        try {
            $action->execute(auth()->user(), []);
            flash()->success('Clocked out successfully.');
        } catch (\Throwable $e) {
            flash()->error($e->getMessage());
        }
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
