<?php

declare(strict_types=1);

namespace App\Document\OfficialDocument\Livewire;

use App\Document\Models\Document;
use App\Document\OfficialDocument\Actions\DeleteReportAction;
use App\Document\OfficialDocument\Actions\GenerateReportAction;
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

        flash()->success(__('document.report_generated', ['name' => $report->name]));
    }

    public function deleteReport(Document $report, DeleteReportAction $action): void
    {
        $action->execute($report);
        flash()->success(__('document.report_deleted'));
    }

    #[Layout('core::layouts.app')]
    public function render(): View
    {
        $reports = Document::query()->where('type', 'report')->latest()->paginate(10);

        return view('document.official-document.reports-manager', [
            'reports' => $reports,
            'types' => $this->reportTypes,
        ]);
    }
}
