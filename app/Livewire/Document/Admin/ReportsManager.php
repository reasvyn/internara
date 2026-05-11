<?php

declare(strict_types=1);

namespace App\Livewire\Document\Admin;

use App\Models\Document;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;

class ReportsManager extends Component
{
    use Toast, WithPagination;

    public array $reportTypes = [
        'internship_completion' => 'Internship Completion Summary',
        'student_performance' => 'Student Performance Report',
        'company_participation' => 'Company Participation Record',
        'mentor_evaluation' => 'Mentor Evaluation Summary',
    ];

    public function generateReport(string $type): void
    {
        $report = Document::create([
            'name' => $this->reportTypes[$type] ?? $type,
            'slug' => $type.'-'.now()->timestamp,
            'category' => 'report',
            'description' => 'Auto-generated report',
            'content' => json_encode(['type' => $type, 'generated_at' => now()->toIso8601String()]),
            'is_active' => true,
        ]);

        $this->success("Report '{$report->name}' generated. Use download to retrieve it.");
    }

    public function deleteReport(Document $report): void
    {
        $report->delete();
        $this->success('Report deleted.');
    }

    #[Layout('layouts::app')]
    public function render()
    {
        $reports = Document::query()
            ->where('category', 'report')
            ->latest()
            ->paginate(10);

        return view('livewire.document.reports-manager', [
            'reports' => $reports,
            'types' => $this->reportTypes,
        ]);
    }
}
