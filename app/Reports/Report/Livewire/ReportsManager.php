<?php

declare(strict_types=1);

namespace App\Reports\Report\Livewire;

use App\Core\Exceptions\RejectedException;
use App\Enrollment\Registration\Models\Registration;
use App\Reports\Report\Actions\CalculateFinalGradeAction;
use App\Reports\Report\Actions\CreateReportAction;
use App\Reports\Report\Actions\FinalizeReportAction;
use App\Reports\Report\Data\CreateReportData;
use App\Reports\Report\Enums\ReportStatus;
use App\Reports\Report\Models\Report;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

class ReportsManager extends Component
{
    use WithPagination;

    public string $search = '';

    public ?string $statusFilter = null;

    public bool $createModal = false;

    public ?string $selectedRegistrationId = null;

    public bool $calculateModal = false;

    public ?string $calculateReportId = null;

    public bool $finalizeModal = false;

    public ?string $finalizeReportId = null;

    public bool $showConfirm = false;

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
        return Report::query()
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

    public function createReport(CreateReportAction $action): void
    {
        $this->validate([
            'selectedRegistrationId' => 'required|string|exists:registrations,id',
        ]);

        $this->authorize('create', Report::class);

        try {
            $action->execute(new CreateReportData(
                registrationId: $this->selectedRegistrationId,
            ));

            flash()->success(__('reports.created'));
        } catch (RejectedException $e) {
            flash()->error($e->getMessage());
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
        $this->authorize('calculate', Report::class);

        $report = Report::findOrFail($this->calculateReportId);

        try {
            $action->execute($report);

            flash()->success(__('reports.grade_calculated'));
        } catch (RejectedException $e) {
            flash()->error($e->getMessage());
        }

        $this->calculateModal = false;
    }

    public function openFinalizeModal(string $reportId): void
    {
        $this->resetErrorBag();
        $this->finalizeReportId = $reportId;
        $this->finalizeModal = true;
    }

    public function finalizeReport(FinalizeReportAction $action): void
    {
        $this->authorize('finalize', Report::class);

        $report = Report::findOrFail($this->finalizeReportId);

        if (! $report->final_score || ! $report->grade_letter) {
            flash()->error(__('reports.grade_required_before_finalize'));

            return;
        }

        try {
            $action->execute($report, auth()->id());

            flash()->success(__('reports.finalized'));
        } catch (RejectedException $e) {
            flash()->error($e->getMessage());
        }

        $this->finalizeModal = false;
    }

    public function askDelete(string $reportId): void
    {
        $this->confirmAction = 'delete';
        $this->confirmReportId = $reportId;
        $this->showConfirm = true;
    }

    public function confirmDelete(DeleteReportAction $action): void
    {
        $this->authorize('delete', Report::class);

        $report = Report::findOrFail($this->confirmReportId);

        try {
            $action->execute($report);

            flash()->success(__('reports.deleted'));
        } catch (RejectedException $e) {
            flash()->error($e->getMessage());
        }

        $this->showConfirm = false;
        $this->confirmReportId = null;
        $this->confirmAction = '';
    }

    #[Layout('core::layouts.app')]
    public function render(): View
    {
        return view('reports.report.reports-manager', [
            'statuses' => ReportStatus::cases(),
        ]);
    }
}
