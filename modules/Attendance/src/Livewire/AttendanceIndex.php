<?php

declare(strict_types=1);

namespace Modules\Attendance\Livewire;

use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;
use Modules\Attendance\Services\Contracts\AttendanceService;

class AttendanceIndex extends Component
{
    use WithPagination;

    public ?string $date_from = null;

    public ?string $date_to = null;

    public ?string $search = null;

    // Flexible Attendance Form
    public bool $attendanceModal = false;

    public string $form_date = '';

    public string $form_status = 'present';

    public string $form_notes = '';

    protected AttendanceService $attendanceService;

    /**
     * Inject dependencies.
     */
    public function boot(AttendanceService $attendanceService): void
    {
        $this->attendanceService = $attendanceService;
    }

    /**
     * Show the attendance recording modal.
     */
    public function openAttendanceModal(): void
    {
        $this->form_date = now()->format('Y-m-d');
        $this->form_status = 'present';
        $this->form_notes = '';
        $this->attendanceModal = true;
    }

    /**
     * Submit the attendance record.
     */
    public function submitAttendance(): void
    {
        $this->validate([
            'form_date' => 'required|date',
            'form_status' => 'required|in:present,sick,permitted,unexplained',
            'form_notes' => 'nullable|string|max:500',
        ]);

        try {
            $this->attendanceService->recordAttendance(auth()->id(), [
                'date' => $this->form_date,
                'status' => $this->form_status,
                'notes' => $this->form_notes,
            ]);

            $this->attendanceModal = false;
            flash()->success(__('attendance::messages.check_in_success'));
        } catch (\Throwable $e) {
            flash()->error($e->getMessage());
        }
    }

    /**
     * One-way click for today's presence.
     */
    public function quickCheckIn(): void
    {
        try {
            $this->attendanceService->checkIn(auth()->id());
            flash()->success(__('attendance::messages.check_in_success'));
        } catch (\Throwable $e) {
            flash()->error($e->getMessage());
        }
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
    #[Computed]
    public function logs()
    {
        $this->authorize('viewAny', \Modules\Attendance\Models\AttendanceLog::class);

        $user = auth()->user();
        $filters = [
            'sort_by' => 'date',
            'sort_dir' => 'desc',
            'date_from' => $this->date_from,
            'date_to' => $this->date_to,
        ];

        $columns = ['id', 'registration_id', 'student_id', 'date', 'status', 'notes', 'created_at'];

        if ($user->hasRole('student')) {
            $filters['student_id'] = $user->id;
        } elseif ($user->hasRole(['teacher', 'mentor'])) {
            $query = $this->attendanceService->query($filters, $columns);
            $query->with(['student:id,name']);

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

        return $this->attendanceService->paginate($filters, 15, $columns);
    }

    public function render(): View
    {
        return view('attendance::livewire.attendance-index')->layout(
            'ui::components.layouts.dashboard',
            [
                'title' => __('attendance::ui.index.title') . ' | ' . setting('brand_name', setting('app_name')),
            ],
        );
    }
}
