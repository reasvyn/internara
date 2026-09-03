<?php

declare(strict_types=1);

namespace App\Modules\Journals\Domain\Attendance\Livewire;

use App\Modules\Core\Livewire\BaseFormView;
use App\Modules\Journals\Domain\Attendance\Actions\ClockInAction;
use App\Modules\Journals\Domain\Attendance\Actions\ClockOutAction;
use App\Modules\Journals\Domain\Attendance\Actions\ReadUnaccountedDatesAction;
use App\Modules\Journals\Domain\Attendance\Data\ClockInData;
use App\Modules\Journals\Domain\Attendance\Data\ClockOutData;
use App\Modules\Journals\Domain\Attendance\Models\Attendance;
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

    #[Layout('ui::layouts.app')]
    public function render(ReadUnaccountedDatesAction $unaccountedDates): View
    {
        $today = Attendance::where('user_id', auth()->id())
            ->whereDate('date', now()->toDateString())
            ->first();

        $registration = auth()->user()->getActiveRegistration();

        return view('journals.attendance.student-clock-in', [
            'todayAttendance' => $today,
            // Working days with neither attendance nor an absence request: the
            // student is required to file one for each of them.
            'unaccountedDates' => $registration
                ? $unaccountedDates->execute($registration)
                : collect(),
        ]);
    }
}
