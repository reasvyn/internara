<?php

declare(strict_types=1);

namespace App\Incident\IncidentReport\Livewire;

use App\Core\Livewire\BaseRecordManager;
use App\Incident\IncidentReport\Actions\ResolveIncidentAction;
use App\Incident\IncidentReport\Enums\IncidentSeverity;
use App\Incident\IncidentReport\Enums\IncidentStatus;
use App\Incident\IncidentReport\Enums\IncidentType;
use App\Incident\IncidentReport\Models\IncidentReport;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\View\View;
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
            ->join('registrations', 'incident_reports.registration_id', '=', 'registrations.id')
            ->join('users', 'incident_reports.reported_by', '=', 'users.id');
    }

    protected function applySearch(Builder $query): Builder
    {
        return $query->where(function (Builder $q) {
            $q->where('users.name', 'like', "%{$this->search}%")->orWhere(
                'incident_reports.description',
                'like',
                "%{$this->search}%",
            );
        });
    }

    protected function applyFilters(Builder $query): Builder
    {
        return $query
            ->when(
                $this->filters['type'] ?? null,
                fn ($q, $v) => $q->where('incident_reports.type', $v),
            )
            ->when(
                $this->filters['severity'] ?? null,
                fn ($q, $v) => $q->where('incident_reports.severity', $v),
            )
            ->when(
                $this->filters['status'] ?? null,
                fn ($q, $v) => $q->where('incident_reports.status', $v),
            );
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

    #[Layout('core::layouts.app')]
    public function render(): View
    {
        return view('incident.incident-report.incident-manager', [
            'typeOptions' => IncidentType::cases(),
            'severityOptions' => IncidentSeverity::cases(),
            'statusOptions' => IncidentStatus::cases(),
        ]);
    }
}
