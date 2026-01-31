<?php

declare(strict_types=1);

namespace Modules\Attendance\Livewire;

use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithPagination;
use Modules\Attendance\Services\Contracts\AttendanceService;

class AttendanceIndex extends Component
{
    use WithPagination;

    public ?string $date_from = null;

    public ?string $date_to = null;

    public ?string $search = null;

    // Absence Request Form
    public bool $absenceModal = false;

    public string $absence_date = '';

    public string $absence_type = 'leave';

    public string $absence_reason = '';

    protected AttendanceService $attendanceService;

    /**
     * Inject dependencies.
     */
    public function boot(AttendanceService $attendanceService): void
    {
        $this->attendanceService = $attendanceService;
    }

    /**
     * Show the absence request modal.
     */
    public function openAbsenceModal(): void
    {
        $this->absence_date = now()->format('Y-m-d');
        $this->absenceModal = true;
    }

    /**
     * Submit the absence request.
     */
    public function submitAbsence(): void
    {
        $this->validate([
            'absence_date' => 'required|date',
            'absence_type' => 'required|in:leave,sick,permit',
            'absence_reason' => 'required|string|max:500',
        ]);

        $user = auth()->user();
        $registration = app(
            \Modules\Internship\Services\Contracts\RegistrationService::class,
        )->first([
            'student_id' => $user->id,
            'latest_status' => 'active',
        ]);

        if (! $registration) {
            $this->dispatch(
                'notify',
                message: __('internship::messages.no_active_registration'),
                type: 'error',
            );

            return;
        }

        $this->attendanceService->createAbsenceRequest([
            'registration_id' => $registration->id,
            'student_id' => $user->id,
            'date' => $this->absence_date,
            'type' => $this->absence_type,
            'reason' => $this->absence_reason,
        ]);

        $this->absenceModal = false;
        $this->absence_reason = '';
        $this->dispatch(
            'notify',
            message: __('attendance::messages.absence_request_submitted'),
            type: 'success',
        );
    }

    /**
     * Reset pagination when filters change.
     */
    public function updated($property): void
    {
        if (in_array($property, ['date_from', 'date_to', 'search'])) {
            $this->resetPage();
        }
    }

    /**
     * Get the attendance logs based on user role.
     */
    public function getLogsProperty()
    {
        $this->authorize('viewAny', \Modules\Attendance\Models\AttendanceLog::class);

        $user = auth()->user();
        $filters = [
            'sort_by' => 'date',
            'sort_dir' => 'desc',
            'date_from' => $this->date_from,
            'date_to' => $this->date_to,
        ];

        if ($user->hasRole('student')) {
            $filters['student_id'] = $user->id;
        } elseif ($user->hasRole(['teacher', 'mentor'])) {
            $query = $this->attendanceService->query($filters);

            // Filter by assigned students
            $query->whereHas('registration', function ($q) use ($user) {
                $q->where('teacher_id', $user->id)->orWhere('mentor_id', $user->id);
            });

            // Filter by student name if search is provided
            if ($this->search) {
                $query->whereHas('student', function ($q) {
                    $q->where('name', 'like', '%'.$this->search.'%');
                });
            }

            return $query->paginate(15);
        }

        return $this->attendanceService->paginate($filters, 15);
    }

    public function render(): View
    {
        return view('attendance::livewire.attendance-index')->layout(
            'ui::components.layouts.dashboard',
            [
                'title' => __('Log Presensi'),
            ],
        );
    }
}
