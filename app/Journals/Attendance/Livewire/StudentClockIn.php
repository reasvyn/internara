<?php

declare(strict_types=1);

namespace App\Journals\Attendance\Livewire;

use App\Core\Livewire\BaseFormView;
use App\Journals\Attendance\Actions\ClockInAction;
use App\Journals\Attendance\Actions\ClockOutAction;
use App\Journals\Attendance\Data\ClockInData;
use App\Journals\Attendance\Data\ClockOutData;
use App\Journals\Attendance\Models\Attendance;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use TallStackUi\Traits\Interactions;

class StudentClockIn extends BaseFormView
{
    use Interactions;

    public function clockIn(ClockInAction $action): void
    {
        $this->handleSave(function () use ($action) {
            $action->execute(new ClockInData(
                userId: auth()->id(),
                data: [],
                requestIp: request()->ip(),
            ));
            $this->toast()->success(__('journals.attendance.clocked_in'))->send();
        });
    }

    public function clockOut(ClockOutAction $action): void
    {
        $this->handleSave(function () use ($action) {
            $action->execute(new ClockOutData(
                userId: auth()->id(),
                data: [],
            ));
            $this->toast()->success(__('journals.attendance.clocked_out'))->send();
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
