<?php

declare(strict_types=1);

namespace App\Livewire\Incident;

use App\Actions\Incident\ResolveIncidentAction;
use App\Enums\Incident\IncidentSeverity;
use App\Enums\Incident\IncidentStatus;
use App\Enums\Incident\IncidentType;
use App\Livewire\Core\BaseRecordManager;
use App\Models\IncidentReport;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Layout;

class IncidentManager extends BaseRecordManager
{
    public bool $showResolveModal = false;

    public ?string $resolvingId = null;

    public array $resolveData = [
        'resolution_notes' => '',
        'status' => 'resolved',
    ];

    public function headers(): array
    {
        return [
            ['key' => 'incident_date', 'label' => __('incident.date'), 'sortable' => true],
            ['key' => 'student_name', 'label' => __('incident.student'), 'sortable' => true],
            ['key' => 'type', 'label' => __('incident.type'), 'sortable' => true],
            ['key' => 'severity', 'label' => __('incident.severity'), 'sortable' => true],
            ['key' => 'status', 'label' => __('incident.status'), 'sortable' => true],
            ['key' => 'actions', 'label' => '', 'sortable' => false],
        ];
    }

    protected function query(): Builder
    {
        return IncidentReport::query()
            ->select(['incident_reports.*', 'users.name as student_name'])
            ->join('internship_registrations', 'incident_reports.registration_id', '=', 'internship_registrations.id')
            ->join('users', 'incident_reports.reported_by', '=', 'users.id');
    }

    protected function applySearch(Builder $query): Builder
    {
        return $query
            ->where(function (Builder $q) {
                $q->where('users.name', 'like', "%{$this->search}%")
                    ->orWhere('incident_reports.description', 'like', "%{$this->search}%");
            });
    }

    protected function applyFilters(Builder $query): Builder
    {
        return $query
            ->when($this->filters['type'] ?? null, fn ($q, $v) => $q->where('incident_reports.type', $v))
            ->when($this->filters['severity'] ?? null, fn ($q, $v) => $q->where('incident_reports.severity', $v))
            ->when($this->filters['status'] ?? null, fn ($q, $v) => $q->where('incident_reports.status', $v));
    }

    public function resolve(IncidentReport $incident): void
    {
        $this->resetErrorBag();
        $this->resolvingId = $incident->id;
        $this->resolveData = [
            'resolution_notes' => '',
            'status' => 'resolved',
        ];
        $this->showResolveModal = true;
    }

    public function saveResolve(ResolveIncidentAction $resolveAction): void
    {
        $this->validate([
            'resolveData.resolution_notes' => ['required', 'string', 'max:5000'],
            'resolveData.status' => ['required', 'in:resolved,closed'],
        ]);

        $incident = IncidentReport::findOrFail($this->resolvingId);
        $resolveAction->execute($incident, $this->resolveData);
        flash()->success(__('incident.resolve_success'));
        $this->showResolveModal = false;
        $this->resolvingId = null;
    }

    #[Layout('layouts::app')]
    public function render()
    {
        return view('livewire.incident.incident-manager', [
            'typeOptions' => IncidentType::cases(),
            'severityOptions' => IncidentSeverity::cases(),
            'statusOptions' => IncidentStatus::cases(),
        ]);
    }
}
