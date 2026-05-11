<?php

declare(strict_types=1);

namespace App\Livewire\Attendance;

use App\Actions\Attendance\ClockInAction;
use App\Actions\Attendance\ClockOutAction;
use App\Models\Attendance;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Mary\Traits\Toast;

class StudentClockIn extends Component
{
    use Toast;

    public function clockIn(ClockInAction $action): void
    {
        try {
            $action->execute(auth()->user(), []);
            $this->success('Clocked in successfully.');
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
    }

    public function clockOut(ClockOutAction $action): void
    {
        try {
            $action->execute(auth()->user(), []);
            $this->success('Clocked out successfully.');
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
    }

    #[Layout('layouts::app')]
    public function render()
    {
        $today = Attendance::where('user_id', auth()->id())
            ->whereDate('date', now()->toDateString())
            ->first();

        return view('livewire.attendance.student-clock-in', [
            'todayAttendance' => $today,
        ]);
    }
}
