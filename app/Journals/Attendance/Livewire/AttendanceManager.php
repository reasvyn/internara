<?php

declare(strict_types=1);

namespace App\Journals\Attendance\Livewire;

use App\Enrollment\Models\Registration;
use App\Journals\AbsenceRequest\Actions\ProcessAbsenceAction;
use App\Journals\AbsenceRequest\Enums\AbsenceRequestStatus;
use App\Journals\AbsenceRequest\Models\AbsenceRequest;
use App\Journals\Attendance\Actions\CreateAttendanceAction;
use App\Journals\Attendance\Actions\VerifyAttendanceAction;
use App\Journals\Attendance\Enums\AttendanceStatus;
use App\Journals\Attendance\Models\Attendance;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

class AttendanceManager extends Component
{
    use WithPagination;

    public string $date = '';

    public array $records = [];

    public string $tab = 'attendance';

    public function mount(): void
    {
        $this->date = now()->toDateString();
    }

    #[Computed]
    public function students()
    {
        return Registration::query()
            ->with(['mentee.user', 'placement.company'])
            ->whereHas('statuses', fn ($q) => $q->where('name', 'active'))
            ->whereHas('mentors', fn ($q) => $q->where('user_id', auth()->id()))
            ->get();
    }

    #[Computed]
    public function pendingAbsences()
    {
        return AbsenceRequest::with(['user', 'registration.placement.company'])
            ->whereHas(
                'registration',
                fn ($q) => $q->whereHas('mentors', fn ($q) => $q->where('user_id', auth()->id())),
            )
            ->where('status', AbsenceRequestStatus::PENDING)
            ->latest()
            ->paginate(20);
    }

    public function markAttendance(CreateAttendanceAction $action): void
    {
        $this->validate([
            'date' => 'required|date',
            'records' => 'required|array|min:1',
            'records.*.status' => 'required|string|in:present,late,early_out,absent,permission,sick',
        ]);

        $registrationIds = array_keys($this->records);
        $registrations = Registration::with('mentee')
            ->whereIn('id', $registrationIds)
            ->get()
            ->keyBy('id');

        foreach ($this->records as $registrationId => $data) {
            $registration = $registrations->get($registrationId);
            if (! $registration) {
                continue;
            }

            try {
                $action->execute(auth()->user(), [
                    'registration_id' => $registrationId,
                    'user_id' => $registration->mentee->user_id,
                    'date' => $this->date,
                    'status' => $data['status'],
                    'notes' => $data['notes'] ?? null,
                ]);
            } catch (\Throwable $e) {
                flash()->error("Failed to record attendance: {$e->getMessage()}");
            }
        }

        flash()->success('Attendance recorded successfully.');
        $this->records = [];
    }

    public function verifyAttendance(Attendance $log, VerifyAttendanceAction $action): void
    {
        $action->execute($log);
        flash()->success('Attendance verified.');
    }

    public function approveAbsence(string $id, ProcessAbsenceAction $action): void
    {
        $absence = AbsenceRequest::findOrFail($id);
        $action->execute($absence, auth()->user(), AbsenceRequestStatus::APPROVED);
        flash()->success('Absence request approved.');
    }

    public function rejectAbsence(string $id, ProcessAbsenceAction $action): void
    {
        $absence = AbsenceRequest::findOrFail($id);
        $action->execute($absence, auth()->user(), AbsenceRequestStatus::REJECTED);
        flash()->success('Absence request rejected.');
    }

    #[Layout('core::layouts.app')]
    public function render(): View
    {
        $existing = Attendance::query()
            ->whereDate('date', $this->date)
            ->get()
            ->keyBy('registration_id');

        return view('journals.attendance.attendance-manager', [
            'students' => $this->students(),
            'existing' => $existing,
            'statuses' => AttendanceStatus::cases(),
        ]);
    }
}
