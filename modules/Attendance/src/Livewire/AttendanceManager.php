<?php

declare(strict_types=1);

namespace Modules\Attendance\Livewire;

use Illuminate\View\View;
use Livewire\Component;
use Modules\Attendance\Models\AttendanceLog;
use Modules\Attendance\Services\Contracts\AttendanceService;

class AttendanceManager extends Component
{
    public ?AttendanceLog $todayLog = null;

    protected AttendanceService $attendanceService;

    /**
     * Inject dependencies.
     */
    public function boot(AttendanceService $attendanceService): void
    {
        $this->attendanceService = $attendanceService;
    }

    public function mount(): void
    {
        $this->loadTodayLog();
    }

    /**
     * Load the today's attendance log for the current student.
     */
    public function loadTodayLog(): void
    {
        if (auth()->user()->hasRole('student')) {
            $this->todayLog = $this->attendanceService->getTodayLog((string) auth()->id());
        }
    }

    /**
     * Perform clock in action.
     */
    public function clockIn(): void
    {
        try {
            $this->attendanceService->checkIn((string) auth()->id());
            $this->loadTodayLog();
            $this->dispatch(
                'notify',
                message: __('attendance::messages.check_in_success'),
                type: 'success',
            );
        } catch (\Throwable $e) {
            $message =
                $e instanceof \Modules\Exception\AppException
                    ? $e->getUserMessage()
                    : $e->getMessage();

            $this->dispatch('notify', message: $message, type: 'error');
        }
    }

    /**
     * Perform clock out action.
     */
    public function clockOut(): void
    {
        try {
            $this->attendanceService->checkOut((string) auth()->id());
            $this->loadTodayLog();
            $this->dispatch(
                'notify',
                message: __('attendance::messages.check_out_success'),
                type: 'success',
            );
        } catch (\Throwable $e) {
            $message =
                $e instanceof \Modules\Exception\AppException
                    ? $e->getUserMessage()
                    : $e->getMessage();

            $this->dispatch('notify', message: $message, type: 'error');
        }
    }

    public function render(): View
    {
        return view('attendance::livewire.attendance-manager');
    }
}
