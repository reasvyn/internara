<?php

declare(strict_types=1);

namespace App\Reports\Report\Livewire;

use App\Enrollment\Models\Registration;
use App\Reports\Report\Actions\CreateReportAction;
use App\Reports\Report\Actions\SubmitReportAction;
use App\Reports\Report\Models\Report;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Layout;
use Livewire\Component;

class ReportWriter extends Component
{
    public ?string $reportId = null;

    public string $title = '';

    public string $registrationId = '';

    public string $chapterContent = '';

    public function boot(): void
    {
        abort_unless(auth()->user()->hasRole('student'), 403);
    }

    public function mount(): void
    {
        $report = Report::whereHas(
            'registration',
            fn (Builder $q) => $q->whereHas(
                'mentee',
                fn (Builder $q) => $q->where('user_id', auth()->id()),
            ),
        )->first();

        if ($report) {
            $this->reportId = $report->id;
            $this->title = $report->title;
            $this->registrationId = $report->registration_id;
            $this->chapterContent = json_encode($report->content, JSON_PRETTY_PRINT) ?: '';
        }
    }

    public function saveDraft(CreateReportAction $createAction): void
    {
        $this->validate([
            'title' => 'required|string|max:255',
            'registrationId' => 'required|exists:registrations,id',
        ]);

        if ($this->reportId) {
            $report = Report::findOrFail($this->reportId);
            $report->update(['content' => json_decode($this->chapterContent, true) ?? []]);
            flash()->success(__('report.saved'));
        } else {
            $report = $createAction->execute([
                'registration_id' => $this->registrationId,
                'title' => $this->title,
            ]);
            $this->reportId = $report->id;
            flash()->success(__('report.created'));
        }
    }

    public function submit(SubmitReportAction $submitAction): void
    {
        if (! $this->reportId) {
            flash()->error(__('report.save_first'));

            return;
        }

        $report = Report::findOrFail($this->reportId);
        $content = json_decode($this->chapterContent, true) ?? [];
        $submitAction->execute($report, $content);
        flash()->success(__('report.submitted'));
    }

    #[Layout('core::layouts.app')]
    public function render(): View
    {
        return view('reports.report.report-writer', [
            'registrations' => Registration::query()
                ->whereHas('mentee', fn (Builder $q) => $q->where('user_id', auth()->id()))
                ->whereIn('status', ['active', 'completed'])
                ->with('internship')
                ->get(),
        ]);
    }
}
