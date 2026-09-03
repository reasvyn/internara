<?php

declare(strict_types=1);

namespace App\Modules\Reports\Domain\StudentReport\Livewire;

use App\Modules\Core\Exceptions\RejectedException;
use App\Modules\Enrollment\Domain\Registration\Models\Registration;
use App\Modules\Reports\Domain\StudentReport\Actions\CalculateFinalGradeAction;
use App\Modules\Reports\Domain\StudentReport\Actions\CreateStudentReportAction;
use App\Modules\Reports\Domain\StudentReport\Actions\DeleteStudentReportAction;
use App\Modules\Reports\Domain\StudentReport\Actions\FinalizeStudentReportAction;
use App\Modules\Reports\Domain\StudentReport\Data\CreateStudentReportData;
use App\Modules\Reports\Domain\StudentReport\Enums\StudentReportStatus;
use App\Modules\Reports\Domain\StudentReport\Models\StudentReport;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;
use TallStackUi\Traits\Interactions;

class StudentReportsManager extends Component
{
    use Interactions;
    use WithPagination;

    /** @var bool Whether the shared confirm dialog is open (see ui.components.confirm). */
    public bool $showConfirm = false;

    public string $search = '';

    public ?string $statusFilter = null;

    public bool $createModal = false;

    public ?string $selectedRegistrationId = null;

    public bool $calculateModal = false;

    public ?string $calculateReportId = null;

    public bool $finalizeModal = false;

    public ?string $finalizeReportId = null;

    public string $confirmAction = '';

    public ?string $confirmReportId = null;

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

    #[Computed]
    public function reports()
    {
        return StudentReport::query()
            ->with(['registration.student.profile', 'registration.internship', 'finalizedBy'])
            ->when($this->search, fn ($q) => $q->whereHas('registration.student', fn ($q) => $q->where('name', 'like', "%{$this->search}%")))
            ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
            ->latest()
            ->paginate(15);
    }

    public function openCreateModal(): void
    {
        $this->resetErrorBag();
        $this->selectedRegistrationId = null;
        $this->createModal = true;
    }

    public function createReport(CreateStudentReportAction $action): void
    {
        $this->validate([
            'selectedRegistrationId' => 'required|string|exists:registrations,id',
        ]);

        $this->authorize('create', StudentReport::class);

        try {
            $action->execute(new CreateStudentReportData(
                registrationId: $this->selectedRegistrationId,
            ));

            $this->toast()->success(__('report.created'))->send();
        } catch (RejectedException $e) {
            $this->toast()->error($e->getMessage())->send();
        }

        $this->createModal = false;
    }

    public function openCalculateModal(string $reportId): void
    {
        $this->resetErrorBag();
        $this->calculateReportId = $reportId;
        $this->calculateModal = true;
    }

    public function calculateGrades(CalculateFinalGradeAction $action): void
    {
        $this->authorize('calculate', StudentReport::class);

        $report = StudentReport::findOrFail($this->calculateReportId);

        try {
            $action->execute($report);

            $this->toast()->success(__('report.grade_calculated'))->send();
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
            ->question(__('common.actions.confirm_action'), $this->confirmMessage ?? __('common.actions.confirm_message'))
            ->confirm(text: __('common.actions.confirm'), method: 'confirmAction')
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
        return view('reports.report.reports-manager', [
            'statuses' => StudentReportStatus::cases(),
        ]);
    }
}
