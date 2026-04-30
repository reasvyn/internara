<?php

declare(strict_types=1);

namespace Modules\Attendance\Livewire;

use Illuminate\View\View;
use Livewire\Component;
use Modules\Attendance\Models\AttendanceLog;
use Modules\Attendance\Services\Contracts\AttendanceService;
use Modules\Exception\AppException;
use Modules\Guidance\Services\Contracts\HandbookService;
use Modules\Permission\Enums\Permission;
use Modules\Setting\Services\Contracts\SettingService;
use Modules\UI\Livewire\Traits\RbacTrait;

class AttendanceManager extends Component
{
    use RbacTrait;

    protected ?Permission $viewPermission = Permission::ATTENDANCE_VIEW;

    protected ?Permission $createPermission = Permission::ATTENDANCE_CREATE;

    protected ?Permission $updatePermission = Permission::ATTENDANCE_UPDATE;

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
            $guidanceService = app(HandbookService::class);
            $settingService = app(SettingService::class);

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
        $guidanceService = app(HandbookService::class);
        $settingService = app(SettingService::class);

        if (
            $settingService->getValue('feature_guidance_enabled', true) &&
            !$guidanceService->hasCompletedMandatory((string) auth()->id())
        ) {
            flash()->warning(__('guidance::messages.must_complete_guidance'));

            return;
        }

        try {
            $this->attendanceService->checkIn((string) auth()->id());
            $this->loadTodayLog();
            flash()->success(__('attendance::messages.check_in_success'));
        } catch (\Throwable $e) {
            $message = $e instanceof AppException ? $e->getUserMessage() : $e->getMessage();

            flash()->error($message);
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
            flash()->success(__('attendance::messages.check_out_success'));
        } catch (\Throwable $e) {
            $message = $e instanceof AppException ? $e->getUserMessage() : $e->getMessage();

            flash()->error($message);
        }
    }

    public function render(): View
    {
        return view('attendance::livewire.attendance-manager');
    }
}
