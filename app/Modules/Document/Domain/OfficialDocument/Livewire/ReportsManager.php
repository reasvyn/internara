<?php

declare(strict_types=1);

namespace App\Modules\Document\Domain\OfficialDocument\Livewire;

use App\Modules\Document\Models\Document;
use App\Modules\Document\Domain\OfficialDocument\Actions\DeleteReportAction;
use App\Modules\Document\Domain\OfficialDocument\Actions\GenerateReportAction;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;
use TallStackUi\Traits\Interactions;

class ReportsManager extends Component
{
    use Interactions;
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

        $this->toast()->success(__('document.report_generated', ['name' => $report->name]))->send();
    }

    public function deleteReport(Document $report, DeleteReportAction $action): void
    {
        $action->execute($report);
        $this->toast()->success(__('document.report_deleted'))->send();
    }

    #[Layout('ui::layouts.app')]
    public function render(): View
    {
        $reports = Document::query()->where('type', 'report')->latest()->paginate(10);

        return view('document.official-document.reports-manager', [
            'reports' => $reports,
            'types' => $this->reportTypes,
        ]);
    }
}
