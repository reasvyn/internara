<?php

declare(strict_types=1);

namespace App\Livewire\Document\Admin;

use App\Domain\Document\Actions\QueueReportGenerationAction;
use App\Domain\Document\Models\GeneratedReport;
use App\Livewire\Core\BaseRecordManager;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Computed;

/**
 * Modernized Reports Manager using BaseRecordManager pattern.
 */
class ReportsManager extends BaseRecordManager
{
    public bool $generateModal = false;

    public array $formData = [
        'report_type' => '',
        'date_from' => '',
        'date_to' => '',
    ];

    public function boot(): void
    {
        if (
            ! auth()
                ->user()
                ?->hasAnyRole(['super_admin', 'admin', 'teacher'])
        ) {
            abort(403, 'Unauthorized access.');
        }
    }

    /**
     * Define columns and sorting.
     */
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

    /**
     * Base query for reports.
     */
    protected function query(): Builder
    {
        return GeneratedReport::where('user_id', auth()->id());
    }

    /**
     * Search implementation.
     */
    protected function applySearch(Builder $query): Builder
    {
        return $query->where('report_type', 'like', "%{$this->search}%");
    }

    /**
     * Filter implementation.
     */
    protected function applyFilters(Builder $query): Builder
    {
        return $query
            ->when($this->filters['report_type'] ?? null, function ($q, $type) {
                $q->where('report_type', $type);
            })
            ->when($this->filters['status'] ?? null, function ($q, $status) {
                $q->where('status', $status);
            });
    }

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

    public function openGenerateModal(): void
    {
        $this->resetErrorBag();
        $this->formData = [
            'report_type' => '',
            'date_from' => '',
            'date_to' => '',
        ];
        $this->generateModal = true;
    }

    public function generateReport(QueueReportGenerationAction $action): void
    {
        $this->validate([
            'formData.report_type' => 'required|string|in:attendance_summary,internship_placements,student_performance,company_overview',
            'formData.date_from' => 'nullable|date',
            'formData.date_to' => 'nullable|date|after_or_equal:formData.date_from',
        ]);

        $filters = array_filter([
            'date_from' => $this->formData['date_from'] ?: null,
            'date_to' => $this->formData['date_to'] ?: null,
        ]);

        $action->execute(auth()->user(), $this->formData['report_type'], $filters);

        $this->generateModal = false;
        $this->success('Report generation has been queued.');
    }

    // --- Bulk Actions ---

    public function deleteSelected(): void
    {
        $this->performBulkAction('Delete', function ($id) {
            $report = GeneratedReport::find($id);
            if ($report) {
                // Should also delete file if exists
                $report->delete();
            }
        });
    }

    // --- Mass Actions ---

    public function cleanFailedReports(): void
    {
        $this->performMassAction('Clean Failed', function ($query) {
            $query->where('status', 'failed')->delete();
        });
    }

    public function render()
    {
        return view('livewire.document.report-index');
    }
}
