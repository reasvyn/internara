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

    protected AttendanceService $attendanceService;

    /**
     * Inject dependencies.
     */
    public function boot(AttendanceService $attendanceService): void
    {
        $this->attendanceService = $attendanceService;
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
            'dashboard::components.layouts.dashboard',
            [
                'title' => __('Log Presensi'),
            ],
        );
    }
}
