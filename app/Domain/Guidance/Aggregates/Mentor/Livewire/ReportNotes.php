<?php

declare(strict_types=1);

namespace App\Domain\Guidance\Aggregates\Mentor\Livewire;

use App\Domain\Reports\Aggregates\Report\Actions\AddSupervisorReportNotesAction;
use App\Domain\Reports\Aggregates\Report\Models\Report;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

class ReportNotes extends Component
{
    public ?string $reportId = null;

    public string $notes = '';

    public function boot(): void
    {
        abort_unless(auth()->user()->hasRole('supervisor'), 403);
    }

    public function mount(): void
    {
        $report = Report::whereHas('registration.mentors', fn (Builder $q) => $q
            ->where('users.id', auth()->id()))
            ->first();

        if ($report) {
            $this->reportId = $report->id;
            $this->notes = $report->supervisor_notes ?? '';
        }
    }

    public function save(AddSupervisorReportNotesAction $action): void
    {
        $this->validate([
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        if ($this->reportId) {
            $report = Report::findOrFail($this->reportId);
            $action->execute($report, $this->notes);
            flash()->success(__('report.supervisor_notes_saved'));
        }
    }

    #[Layout('core::layouts.app')]
    public function render(): View
    {
        return view('guidance.mentor.report.notes');
    }
}
