<?php

declare(strict_types=1);

namespace App\Livewire\Mentor\Supervision;

use App\Actions\Mentor\CreateSupervisionLogAction;
use App\Actions\Mentor\VerifySupervisionLogAction;
use App\Models\SupervisionLog;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

class SupervisorLogManager extends Component
{
    use WithPagination;

    public bool $showModal = false;

    public string $registrationId = '';

    public string $date = '';

    public string $topic = '';

    public string $notes = '';

    public string $type = 'guidance';

    public function mount(): void
    {
        $this->date = now()->toDateString();
    }

    #[Computed]
    public function students()
    {
        return Registration::query()
            ->with(['student'])
            ->where('teacher_id', auth()->id())
            ->orWhere('mentor_id', auth()->id())
            ->get();
    }

    public function create(): void
    {
        $this->reset(['registrationId', 'topic', 'notes']);
        $this->date = now()->toDateString();
        $this->showModal = true;
    }

    public function save(CreateSupervisionLogAction $createAction): void
    {
        $this->validate([
            'registrationId' => 'required|exists:internship_registrations,id',
            'date' => 'required|date',
            'topic' => 'required|string|max:255',
            'notes' => 'required|string',
        ]);

        $createAction->execute(
            auth()->user(),
            $this->registrationId,
            [
                'date' => $this->date,
                'topic' => $this->topic,
                'notes' => $this->notes,
            ],
        );

        $this->showModal = false;
        flash()->success('Supervision log recorded successfully.');
    }

    public function verify(SupervisionLog $log, VerifySupervisionLogAction $verifyAction): void
    {
        $verifyAction->execute($log, auth()->user());
        flash()->success('Log verified successfully.');
    }

    #[Layout('layouts::app')]
    public function render()
    {
        $logs = SupervisionLog::query()
            ->where('supervisor_id', auth()->id())
            ->with(['registration.student'])
            ->latest('date')
            ->paginate(10);

        return view('livewire.mentor.supervisor-log-manager', [
            'logs' => $logs,
        ]);
    }
}
