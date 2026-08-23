<?php

declare(strict_types=1);

namespace App\Journals\Attendance\Livewire;

use App\Core\Exceptions\RejectedException;
use App\Enrollment\Registration\Models\Registration;
use App\Journals\AbsenceRequest\Actions\ProcessAbsenceAction;
use App\Journals\AbsenceRequest\Enums\AbsenceRequestStatus;
use App\Journals\AbsenceRequest\Models\AbsenceRequest;
use App\Journals\Attendance\Actions\CreateAttendanceAction;
use App\Journals\Attendance\Actions\DeleteAttendanceAction;
use App\Journals\Attendance\Actions\UpdateAttendanceAction;
use App\Journals\Attendance\Actions\VerifyAttendanceAction;
use App\Journals\Attendance\Enums\AttendanceStatus;
use App\Journals\Attendance\Models\Attendance;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Validator;
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
            ->where('status', 'active')
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
            'records.*.status' => 'required|'.$this->statusRule(),
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
                flash()->error(__('journals.attendance.record_failed', ['message' => $e->getMessage()]));
            }
        }

        flash()->success(__('journals.attendance.recorded'));
        $this->records = [];
    }

    public function verifyAttendance(Attendance $log, VerifyAttendanceAction $action): void
    {
        $action->execute($log);
        flash()->success(__('journals.attendance.verified'));
    }

    public function updateAttendance(string $id, string $status, UpdateAttendanceAction $action): void
    {
        $attendance = Attendance::findOrFail($id);

        Validator::validate(['status' => $status], ['status' => $this->statusRule()]);

        $this->authorize('update', $attendance);

        try {
            $action->execute($attendance, ['status' => $status]);
            flash()->success(__('journals.attendance.updated'));
        } catch (RejectedException $e) {
            flash()->error($e->getMessage());
        }
    }

    public function deleteAttendance(string $id, DeleteAttendanceAction $action): void
    {
        $attendance = Attendance::findOrFail($id);

        $this->authorize('delete', $attendance);

        $action->execute($attendance);
        flash()->success(__('journals.attendance.deleted'));
    }

    private function statusRule(): string
    {
        return 'in:'.implode(',', array_column(AttendanceStatus::cases(), 'value'));
    }

    public function approveAbsence(string $id, ProcessAbsenceAction $action): void
    {
        $absence = AbsenceRequest::findOrFail($id);
        $this->authorize('update', $absence);
        $action->execute(new ProcessAbsenceData(
            absenceId: $absence->id,
            processorId: auth()->id(),
            status: AbsenceRequestStatus::APPROVED,
        ));
        flash()->success(__('journals.absence.approved'));
    }

    public function rejectAbsence(string $id, ProcessAbsenceAction $action): void
    {
        $absence = AbsenceRequest::findOrFail($id);
        $this->authorize('update', $absence);
        $action->execute(new ProcessAbsenceData(
            absenceId: $absence->id,
            processorId: auth()->id(),
            status: AbsenceRequestStatus::REJECTED,
        ));
        flash()->success(__('journals.absence.rejected'));
    }

    #[Layout('core::layouts.app')]
    public function render(): View
    {
        $students = $this->students();

        $existing = Attendance::query()
            ->whereDate('date', $this->date)
            ->whereIn('registration_id', $students->pluck('id'))
            ->get()
            ->keyBy('registration_id');

        return view('journals.attendance.attendance-manager', [
            'students' => $students,
            'existing' => $existing,
            'statuses' => AttendanceStatus::cases(),
        ]);
    }
}
