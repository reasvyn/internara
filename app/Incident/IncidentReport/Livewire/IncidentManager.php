<?php

declare(strict_types=1);

namespace App\Incident\IncidentReport\Livewire;

use App\Core\Exceptions\RejectedException;
use App\Core\Livewire\BaseRecordManager;
use App\Incident\IncidentReport\Actions\ResolveIncidentAction;
use App\Incident\IncidentReport\Actions\UpdateIncidentAction;
use App\Incident\IncidentReport\Enums\IncidentSeverity;
use App\Incident\IncidentReport\Enums\IncidentStatus;
use App\Incident\IncidentReport\Enums\IncidentType;
use App\Incident\IncidentReport\Models\IncidentReport;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use TallStackUi\Traits\Interactions;

class IncidentManager extends BaseRecordManager
{
    use Interactions;

    public bool $showResolveModal = false;

    public ?string $resolvingId = null;

    public array $resolveData = [
        'resolution_notes' => '',
        'status' => 'resolved',
    ];

    public bool $showEditModal = false;

    public ?string $editingId = null;

    public array $editData = [
        'incident_date' => '',
        'type' => '',
        'severity' => '',
        'description' => '',
        'location' => '',
        'action_taken' => '',
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
        $this->toast()->success(__('incident.resolve_success'))->send();
        $this->showResolveModal = false;
        $this->resolvingId = null;
    }

    public function edit(IncidentReport $incident): void
    {
        $this->resetErrorBag();
        $this->editingId = $incident->id;
        $this->editData = [
            'incident_date' => $incident->incident_date?->toDateTimeString() ?? '',
            'type' => $incident->type->value,
            'severity' => $incident->severity->value,
            'description' => $incident->description ?? '',
            'location' => $incident->location ?? '',
            'action_taken' => $incident->action_taken ?? '',
        ];
        $this->showEditModal = true;
    }

    public function saveEdit(UpdateIncidentAction $action): void
    {
        $this->validate([
            'editData.incident_date' => ['required', 'date'],
            'editData.type' => ['required', 'in:accident,safety_violation,harassment,disciplinary,other'],
            'editData.severity' => ['required', 'in:low,medium,high,critical'],
            'editData.description' => ['required', 'string', 'max:5000'],
            'editData.location' => ['nullable', 'string', 'max:255'],
            'editData.action_taken' => ['nullable', 'string', 'max:2000'],
        ]);

        $incident = IncidentReport::findOrFail($this->editingId);

        $this->authorize('update', $incident);

        try {
            $action->execute($incident, $this->editData);
            $this->toast()->success(__('incident.update_success'))->send();
            $this->showEditModal = false;
            $this->editingId = null;
        } catch (RejectedException $e) {
            $this->toast()->error($e->getMessage())->send();
        }
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
