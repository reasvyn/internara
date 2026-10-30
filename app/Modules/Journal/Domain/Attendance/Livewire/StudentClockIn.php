<?php

declare(strict_types=1);

namespace App\Modules\Journal\Domain\Attendance\Livewire;

use App\Modules\Journal\Domain\Attendance\Actions\ClockInAction;
use App\Modules\Journal\Domain\Attendance\Actions\ClockOutAction;
use App\Modules\Journal\Domain\Attendance\Actions\ReadUnaccountedDatesAction;
use App\Modules\Journal\Domain\Attendance\Data\ClockInData;
use App\Modules\Journal\Domain\Attendance\Data\ClockOutData;
use App\Modules\Journal\Domain\Attendance\Models\Attendance;
use App\Modules\UI\Livewire\BaseFormView;
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
            $this->toast()->success(__('journal.attendance.clocked_in'))->send();
        });
    }

    public function clockOut(ClockOutAction $action): void
    {
        $this->handleSave(function () use ($action) {
            $action->execute(new ClockOutData(
                userId: auth()->id(),
                data: [],
            ));
            $this->toast()->success(__('journal.attendance.clocked_out'))->send();
        });
    }

    #[Layout('ui::layouts.app')]
    public function render(ReadUnaccountedDatesAction $unaccountedDates): View
    {
        $user = auth()->user();

        $today = Attendance::where('user_id', $user->id)
            ->whereDate('date', now()->toDateString())
            ->first();

        $registration = $user->getActiveRegistration();
        if ($registration) {
            $registration->loadMissing(['placement.company', 'internship']);
        }

        $recentAttendances = Attendance::where('user_id', $user->id)
            ->latest('date')
            ->take(7)
            ->get();

        return view('journal.attendance.student-clock-in', [
            'todayAttendance' => $today,
            'registration' => $registration,
            'recentAttendances' => $recentAttendances,
            'unaccountedDates' => $registration
                ? $unaccountedDates->execute($registration)
                : collect(),
        ]);
    }
}
