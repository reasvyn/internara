<?php

declare(strict_types=1);

namespace App\Modules\Report\Domain\StudentReport\Livewire;

use App\Modules\Core\Exceptions\RejectedException;
use App\Modules\Enrollment\Domain\Registration\Models\Registration;
use App\Modules\Report\Domain\StudentReport\Actions\CalculateFinalGradeAction;
use App\Modules\Report\Domain\StudentReport\Actions\CreateStudentReportAction;
use App\Modules\Report\Domain\StudentReport\Actions\DeleteStudentReportAction;
use App\Modules\Report\Domain\StudentReport\Actions\FinalizeStudentReportAction;
use App\Modules\Report\Domain\StudentReport\Data\CreateStudentReportData;
use App\Modules\Report\Domain\StudentReport\Enums\StudentReportStatus;
use App\Modules\Report\Domain\StudentReport\Models\StudentReport;
use App\Modules\UI\Livewire\BaseRecordManager;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;

class StudentReportsManager extends BaseRecordManager
{
    /** @var bool Whether the shared confirm dialog is open (see ui.components.confirm). */
    public bool $showConfirm = false;

    public bool $createModal = false;

    public ?string $selectedRegistrationId = null;

    public bool $calculateModal = false;

    public ?string $calculateReportId = null;

    public bool $finalizeModal = false;

    public ?string $finalizeReportId = null;

    public string $confirmAction = '';

    public ?string $confirmReportId = null;

    #[Computed]
    public function reports(): LengthAwarePaginator
    {
        return $this->rows();
    }

    public function headers(): array
    {
        return [
            ['index' => 'student', 'label' => __('report.student'), 'sortable' => false],
            ['index' => 'registration', 'label' => __('report.registration'), 'sortable' => false],
            ['index' => 'status', 'label' => __('report.status'), 'sortable' => true],
            ['index' => 'final_score', 'label' => __('report.final_score'), 'sortable' => true],
            ['index' => 'grade_letter', 'label' => __('report.grade_letter'), 'sortable' => true],
            ['index' => 'finalized_at', 'label' => __('report.finalized_at'), 'sortable' => true],
            ['index' => 'actions', 'label' => '', 'sortable' => false],
        ];
    }

    protected function query(): Builder
    {
        return StudentReport::query()
            ->with(['registration.student.profile', 'registration.internship', 'registration.placement.company', 'finalizedBy']);
    }

    protected function applySearch(Builder $query): Builder
    {
        return $query->whereHas('registration.student', fn ($q) => $q->where('name', 'like', "%{$this->search}%"));
    }

    protected function applyFilters(Builder $query): Builder
    {
        return $query->when($this->filters['status'] ?? null, fn ($q, $v) => $q->where('status', $v));
    }

    #[Computed]
    public function registrations()
    {
        return Registration::query()
            ->where('status', 'active')
            ->whereDoesntHave('report')
            ->with(['student.profile', 'internship', 'placement.company'])
            ->latest()
            ->get();
    }

    public function openCreateModal(): void
    {
        $this->resetErrorBag();
        $this->selectedRegistrationId = null;
        $this->createModal = true;
    }

    public function createReport(CreateStudentReportAction $action): void
    {
        $this->authorize('create', StudentReport::class);

        $this->validate([
            'selectedRegistrationId' => ['required', 'exists:registrations,id'],
        ]);

        $registration = Registration::findOrFail($this->selectedRegistrationId);

        try {
            $report = $action->execute(new CreateStudentReportData(
                registrationId: $registration->id,
            ));

            $this->toast()->success(__('report.created'))->send();
            $this->createModal = false;
            $this->selectedRegistrationId = null;
        } catch (RejectedException $e) {
            $this->toast()->error($e->getMessage())->send();
        }
    }

    public function openCalculateModal(string $reportId): void
    {
        $this->resetErrorBag();
        $this->calculateReportId = $reportId;
        $this->calculateModal = true;
    }

    public function calculateGrades(CalculateFinalGradeAction $action): void
    {
        $this->authorize('update', StudentReport::class);

        $report = StudentReport::findOrFail($this->calculateReportId);

        try {
            $action->execute($report);

            $this->toast()->success(__('report.grades_calculated'))->send();
        } catch (RejectedException $e) {
            $this->toast()->error($e->getMessage())->send();
        }

        $this->calculateModal = false;
    }

    public function openFinalizeModal(string $reportId): void
    {
        $this->resetErrorBag();
        $this->finalizeReportId = $reportId;
        $this->finalizeModal = true;
    }

    public function finalizeReport(FinalizeStudentReportAction $action): void
    {
        $this->authorize('finalize', StudentReport::class);

        $report = StudentReport::findOrFail($this->finalizeReportId);

        if (! $report->final_score || ! $report->grade_letter) {
            $this->toast()->error(__('report.grade_required_before_finalize'))->send();

            return;
        }

        try {
            $action->execute($report, auth()->id());

            $this->toast()->success(__('report.finalized'))->send();
        } catch (RejectedException $e) {
            $this->toast()->error($e->getMessage())->send();
        }

        $this->finalizeModal = false;
    }

    public function askDelete(string $reportId): void
    {
        $this->confirmAction = 'delete';
        $this->confirmReportId = $reportId;
        $this->dialog()
            ->question(__('common.actions.confirm_action'), __('common.actions.confirm_message'))
            ->confirm(text: __('common.actions.confirm'), method: 'confirmDelete')
            ->cancel(text: __('common.actions.cancel'))
            ->send();
    }

    public function confirmDelete(DeleteStudentReportAction $action): void
    {
        $this->authorize('delete', StudentReport::class);

        $report = StudentReport::findOrFail($this->confirmReportId);

        try {
            $action->execute($report);

            $this->toast()->success(__('report.deleted'))->send();
        } catch (RejectedException $e) {
            $this->toast()->error($e->getMessage())->send();
        }
        $this->confirmReportId = null;
        $this->confirmAction = '';
    }

    #[Layout('ui::layouts.app')]
    public function render(): View
    {
        return view('report.student-report.reports-manager', [
            'statuses' => StudentReportStatus::cases(),
        ]);
    }
}
