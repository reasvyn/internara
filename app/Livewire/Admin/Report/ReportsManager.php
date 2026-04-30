<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Report;

use App\Actions\Report\QueueReportGenerationAction;
use App\Models\GeneratedReport;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;

class ReportsManager extends Component
{
    use WithPagination, Toast;

    public function boot(): void
    {
        if (!auth()->user()?->hasAnyRole(['super_admin', 'admin', 'teacher'])) {
            abort(403, 'Unauthorized access.');
        }
    }

    public string $search = '';

    public array $filters = [
        'report_type' => null,
        'status' => null,
    ];

    public bool $generateModal = false;

    public array $reportData = [
        'report_type' => '',
        'date_from' => '',
        'date_to' => '',
    ];

    #[Computed]
    public function reportTypes(): array
    {
        return [
            ['id' => 'attendance_summary', 'name' => 'Attendance Summary'],
            ['id' => 'internship_placements', 'name' => 'Internship Placements'],
            ['id' => 'student_performance', 'name' => 'Student Performance'],
            ['id' => 'company_overview', 'name' => 'Company Overview'],
        ];
    }

    #[Computed]
    public function statusOptions(): array
    {
        return [
            ['id' => 'pending', 'name' => 'Pending'],
            ['id' => 'completed', 'name' => 'Completed'],
            ['id' => 'failed', 'name' => 'Failed'],
        ];
    }

    public function reports(): LengthAwarePaginator
    {
        $query = auth()->user()->generatedReports()->latest();

        if ($this->search) {
            $query->where('report_type', 'like', "%{$this->search}%");
        }

        if ($this->filters['report_type']) {
            $query->where('report_type', $this->filters['report_type']);
        }

        if ($this->filters['status']) {
            $query->where('status', $this->filters['status']);
        }

        return $query->paginate(20);
    }

    public function headers(): array
    {
        return [
            ['key' => 'report_type', 'label' => 'Report Type', 'sortable' => true],
            ['key' => 'status', 'label' => 'Status', 'class' => 'text-center'],
            ['key' => 'file_size', 'label' => 'Size'],
            ['key' => 'generated_at', 'label' => 'Generated', 'sortable' => true],
            ['key' => 'actions', 'label' => ''],
        ];
    }

    public function openGenerateModal(): void
    {
        $this->resetErrorBag();
        $this->reportData = [
            'report_type' => '',
            'date_from' => '',
            'date_to' => '',
        ];
        $this->generateModal = true;
    }

    public function generateReport(QueueReportGenerationAction $action): void
    {
        $this->validate([
            'reportData.report_type' => 'required|string|in:attendance_summary,internship_placements,student_performance,company_overview',
            'reportData.date_from' => 'nullable|date',
            'reportData.date_to' => 'nullable|date|after_or_equal:reportData.date_from',
        ]);

        $filters = array_filter([
            'date_from' => $this->reportData['date_from'] ?: null,
            'date_to' => $this->reportData['date_to'] ?: null,
        ]);

        $action->execute(auth()->user(), $this->reportData['report_type'], $filters);

        $this->generateModal = false;
        $this->success('Report generation has been queued.');
    }

    public function render()
    {
        return view('livewire.admin.reports.index', [
            'reports' => $this->reports(),
            'headers' => $this->headers(),
        ]);
    }
}
