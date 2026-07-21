<?php

declare(strict_types=1);

namespace App\Journals\SupervisionLog\Livewire;

use App\Enrollment\Registration\Models\Registration;
use App\Journals\SupervisionLog\Actions\CreateSupervisionLogAction;
use App\Journals\SupervisionLog\Actions\VerifySupervisionLogAction;
use App\Journals\SupervisionLog\Models\SupervisionLog;
use Illuminate\View\View;
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
            ->with(['mentee'])
            ->whereHas('mentors', fn ($q) => $q->where('user_id', auth()->id()))
            ->get();
    }

    public function create(): void
    {
        $this->authorize('create', SupervisionLog::class);

        $this->reset(['registrationId', 'topic', 'notes']);
        $this->date = now()->toDateString();
        $this->showModal = true;
    }

    public function save(CreateSupervisionLogAction $createAction): void
    {
        $this->authorize('create', SupervisionLog::class);

        $this->validate([
            'registrationId' => 'required|exists:registrations,id',
            'date' => 'required|date',
            'topic' => 'required|string|max:255',
            'notes' => 'required|string',
        ]);

        $createAction->execute(auth()->user(), $this->registrationId, [
            'date' => $this->date,
            'topic' => $this->topic,
            'notes' => $this->notes,
        ]);

        $this->showModal = false;
        flash()->success('Supervision log recorded successfully.');
    }

    public function verify(SupervisionLog $log, VerifySupervisionLogAction $verifyAction): void
    {
        $this->authorize('update', $log);

        $verifyAction->execute($log, auth()->user());
        flash()->success('Log verified successfully.');
    }

    #[Layout('core::layouts.app')]
    public function render(): View
    {
        $logs = SupervisionLog::query()
            ->where('supervisor_id', auth()->id())
            ->with(['registration.student'])
            ->latest('date')
            ->paginate(10);

        return view('journals.supervision-log.log-manager', [
            'logs' => $logs,
        ]);
    }
}
