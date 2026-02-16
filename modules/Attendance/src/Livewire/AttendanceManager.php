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

        // Gating System: Check if student has completed mandatory guidance
        if (auth()->check() && auth()->user()->hasRole('student')) {
            $guidanceService = app(\Modules\Guidance\Services\Contracts\HandbookService::class);
            $settingService = app(\Modules\Setting\Services\Contracts\SettingService::class);

            if (
                $settingService->getValue('feature_guidance_enabled', true) &&
                !$guidanceService->hasCompletedMandatory((string) auth()->id())
            ) {
                // For manager component which might be embedded, we might just disable actions
                // but for consistency with Journal, let's redirect if it's a main page.
                // However, AttendanceManager is often a small widget.
                // If it's a widget, we should probably just prevent the action in clockIn().
            }
        }
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
        if (auth()->check() && auth()->user()->hasRole('student')) {
            $this->todayLog = $this->attendanceService->getTodayLog((string) auth()->id());
        }
    }

    /**
     * Perform clock in action.
     */
    public function clockIn(): void
    {
        // Gating Check
        $guidanceService = app(\Modules\Guidance\Services\Contracts\HandbookService::class);
        $settingService = app(\Modules\Setting\Services\Contracts\SettingService::class);

        if (
            $settingService->getValue('feature_guidance_enabled', true) &&
            !$guidanceService->hasCompletedMandatory((string) auth()->id())
        ) {
            notify(__('guidance::messages.must_complete_guidance'), 'warning');

            return;
        }

        try {
            $this->attendanceService->checkIn((string) auth()->id());
            $this->loadTodayLog();
            notify(__('attendance::messages.check_in_success'), 'success');
        } catch (\Throwable $e) {
            $message =
                $e instanceof \Modules\Exception\AppException
                    ? $e->getUserMessage()
                    : $e->getMessage();

            notify($message, 'error');
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
            notify(__('attendance::messages.check_out_success'), 'success');
        } catch (\Throwable $e) {
            $message =
                $e instanceof \Modules\Exception\AppException
                    ? $e->getUserMessage()
                    : $e->getMessage();

            notify($message, 'error');
        }
    }

    public function render(): View
    {
        return view('attendance::livewire.attendance-manager');
    }
}
