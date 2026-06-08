<?php

declare(strict_types=1);

namespace App\Incident\IncidentReport\Livewire;

use App\Enrollment\Registration\Models\Registration;
use App\Incident\IncidentReport\Actions\ReportIncidentAction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

class IncidentForm extends Component
{
    public array $formData = [
        'registration_id' => '',
        'incident_date' => '',
        'type' => '',
        'severity' => '',
        'description' => '',
        'location' => '',
        'action_taken' => '',
    ];

    public function boot(): void
    {
        abort_unless(auth()->user()->hasRole('student'), 403);
    }

    public function mount(): void
    {
        $this->formData['incident_date'] = now()->format('Y-m-d\TH:i');
    }

    public function save(ReportIncidentAction $action): void
    {
        $this->validate([
            'formData.registration_id' => ['required', 'exists:registrations,id'],
            'formData.incident_date' => ['required', 'date'],
            'formData.type' => [
                'required',
                'in:accident,safety_violation,harassment,disciplinary,other',
            ],
            'formData.severity' => ['required', 'in:low,medium,high,critical'],
            'formData.description' => ['required', 'string', 'min:20', 'max:5000'],
            'formData.location' => ['nullable', 'string', 'max:255'],
            'formData.action_taken' => ['nullable', 'string', 'max:2000'],
        ]);

        $action->execute([...$this->formData, 'reported_by' => auth()->id()]);

        flash()->success(__('incident.report_success'));

        $this->reset('formData');
        $this->formData['incident_date'] = now()->format('Y-m-d\TH:i');
    }

    #[Layout('core::layouts.app')]
    public function render(): View
    {
        return view('incident.incident-report.incident-form', [
            'registrations' => Registration::query()
                ->whereHas('mentee', fn (Builder $q) => $q->where('user_id', auth()->id()))
                ->where('status', 'active')
                ->with('internship', 'placement.company')
                ->get(),
        ]);
    }
}
