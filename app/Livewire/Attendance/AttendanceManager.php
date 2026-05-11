<?php

declare(strict_types=1);

namespace App\Livewire\Attendance;

use App\Actions\Attendance\CreateAttendanceAction;
use App\Actions\Attendance\VerifyAttendanceAction;
use App\Enums\Attendance\AttendanceStatus;
use App\Models\Attendance;
use App\Models\Registration;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;

class AttendanceManager extends Component
{
    use Toast, WithPagination;

    public string $date = '';

    public array $records = [];

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

    public function markAttendance(CreateAttendanceAction $action): void
    {
        $this->validate([
            'date' => 'required|date',
            'records' => 'required|array|min:1',
            'records.*.status' => 'required|string|in:present,late,early_out,absent,permission,sick',
        ]);

        foreach ($this->records as $registrationId => $data) {
            $registration = Registration::find($registrationId);
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
            } catch (\Exception $e) {
                $this->error("Failed to record attendance: {$e->getMessage()}");
            }
        }

        $this->success('Attendance recorded successfully.');
        $this->records = [];
    }

    public function verifyAttendance(Attendance $log, VerifyAttendanceAction $action): void
    {
        $action->execute($log);
        $this->success('Attendance verified.');
    }

    #[Layout('layouts::app')]
    public function render()
    {
        $existing = Attendance::query()
            ->whereDate('date', $this->date)
            ->get()
            ->keyBy('registration_id');

        return view('livewire.attendance.attendance-manager', [
            'students' => $this->students(),
            'existing' => $existing,
            'statuses' => AttendanceStatus::cases(),
        ]);
    }
}
