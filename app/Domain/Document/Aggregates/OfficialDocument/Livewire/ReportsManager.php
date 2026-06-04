<?php

declare(strict_types=1);

namespace App\Domain\Document\Aggregates\OfficialDocument\Livewire;

use App\Domain\Document\Aggregates\OfficialDocument\Actions\DeleteReportAction;
use App\Domain\Document\Aggregates\OfficialDocument\Actions\GenerateReportAction;
use App\Domain\Document\Models\Document;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

class ReportsManager extends Component
{
    use WithPagination;

    public array $reportTypes = [
        'internship_completion' => 'Internship Completion Summary',
        'student_performance' => 'Student Performance Report',
        'company_participation' => 'Company Participation Record',
        'mentor_evaluation' => 'Mentor Evaluation Summary',
    ];

    public function generateReport(string $type, GenerateReportAction $action): void
    {
        $report = $action->execute([
            'name' => $this->reportTypes[$type] ?? $type,
            'type' => $type,
        ]);

        flash()->success("Report '{$report->name}' generated. Use download to retrieve it.");
    }

    public function deleteReport(Document $report, DeleteReportAction $action): void
    {
        $action->execute($report);
        flash()->success('Report deleted.');
    }

    #[Layout('core::layouts.app')]
    public function render(): View
    {
        $reports = Document::query()
            ->where('category', 'report')
            ->latest()
            ->paginate(10);

        return view('document.reports-manager', [
            'reports' => $reports,
            'types' => $this->reportTypes,
        ]);
    }
}
