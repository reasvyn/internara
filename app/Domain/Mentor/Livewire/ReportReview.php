<?php

declare(strict_types=1);

namespace App\Domain\Mentor\Livewire;

use App\Domain\Core\Livewire\BaseRecordManager;
use App\Domain\Internship\Actions\ApproveReportAction;
use App\Domain\Internship\Actions\RequestReportRevisionAction;
use App\Domain\Internship\Enums\ReportStatus;
use App\Domain\Internship\Models\Report;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Layout;

class ReportReview extends BaseRecordManager
{
    public bool $showGradeModal = false;

    public ?string $gradingReportId = null;

    public array $gradeData = [
        'score' => null,
        'feedback' => '',
    ];

    public string $revisionFeedback = '';

    public function headers(): array
    {
        return [
            ['key' => 'title', 'label' => __('report.title'), 'sortable' => true],
            ['key' => 'student_name', 'label' => __('report.student'), 'sortable' => true],
            ['key' => 'status', 'label' => __('report.status'), 'sortable' => true],
            ['key' => 'submitted_at', 'label' => __('report.submitted_at'), 'sortable' => true],
            ['key' => 'score', 'label' => __('report.score')],
            ['key' => 'actions', 'label' => '', 'sortable' => false],
        ];
    }

    protected function query(): Builder
    {
        return Report::query()
            ->select(['reports.*', 'users.name as student_name'])
            ->join('registrations', 'reports.registration_id', '=', 'registrations.id')
            ->join('mentees', 'registrations.mentee_id', '=', 'mentees.id')
            ->join('users', 'mentees.user_id', '=', 'users.id');
    }

    protected function applySearch(Builder $query): Builder
    {
        return $query->where(function (Builder $q) {
            $q->where('reports.title', 'like', "%{$this->search}%")
                ->orWhere('users.name', 'like', "%{$this->search}%");
        });
    }

    public function grade(Report $report): void
    {
        $this->gradingReportId = $report->id;
        $this->gradeData = ['score' => $report->score, 'feedback' => $report->feedback ?? ''];
        $this->showGradeModal = true;
    }

    public function saveGrade(ApproveReportAction $approveAction): void
    {
        $this->validate([
            'gradeData.score' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'gradeData.feedback' => ['nullable', 'string', 'max:2000'],
        ]);

        $report = Report::findOrFail($this->gradingReportId);
        $approveAction->execute($report, $this->gradeData);
        flash()->success(__('report.approve_success'));
        $this->showGradeModal = false;
    }

    public function requestRevision(Report $report, RequestReportRevisionAction $revisionAction): void
    {
        $this->validate(['revisionFeedback' => 'required|string|max:2000']);
        $revisionAction->execute($report, $this->revisionFeedback);
        flash()->success(__('report.revision_requested'));
        $this->revisionFeedback = '';
    }

    #[Layout('shared::layouts.app')]
    public function render(): View
    {
        return view('mentor.report.review', [
            'statusOptions' => ReportStatus::cases(),
        ]);
    }
}
