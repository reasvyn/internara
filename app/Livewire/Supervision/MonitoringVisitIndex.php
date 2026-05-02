<?php

declare(strict_types=1);

namespace App\Livewire\Supervision;

use App\Actions\Supervision\CreateMonitoringVisitAction;
use App\Models\InternshipRegistration;
use App\Models\MonitoringVisit;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;

class MonitoringVisitIndex extends Component
{
    use Toast, WithPagination;

    public bool $showModal = false;

    public string $registrationId = '';

    public string $date = '';

    public string $notes = '';

    public string $company_feedback = '';

    public string $student_condition = '';

    public function mount(): void
    {
        $this->date = now()->toDateString();
    }

    #[Computed]
    public function students()
    {
        return InternshipRegistration::query()
            ->with(['student', 'placement.company'])
            ->where('teacher_id', auth()->id())
            ->get();
    }

    public function create(): void
    {
        $this->reset(['registrationId', 'notes', 'company_feedback', 'student_condition']);
        $this->date = now()->toDateString();
        $this->showModal = true;
    }

    public function save(CreateMonitoringVisitAction $createAction): void
    {
        $this->validate([
            'registrationId' => 'required|exists:internship_registrations,id',
            'date' => 'required|date',
            'notes' => 'required|string',
        ]);

        $createAction->execute([
            'registration_id' => $this->registrationId,
            'teacher_id' => auth()->id(),
            'date' => $this->date,
            'notes' => $this->notes,
            'company_feedback' => $this->company_feedback,
            'student_condition' => $this->student_condition,
        ]);

        $this->showModal = false;
        $this->success('Monitoring visit recorded successfully.');
    }

    #[Layout('components.layouts.app')]
    public function render()
    {
        $visits = MonitoringVisit::query()
            ->where('teacher_id', auth()->id())
            ->with(['registration.student', 'registration.placement.company'])
            ->latest('date')
            ->paginate(10);

        return view('livewire.supervision.monitoring-visit-index', [
            'visits' => $visits,
        ]);
    }
}
