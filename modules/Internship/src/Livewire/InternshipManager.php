<?php

declare(strict_types=1);

namespace Modules\Internship\Livewire;

use Livewire\Component;
use Modules\Internship\Livewire\Forms\InternshipForm;
use Modules\Internship\Services\Contracts\InternshipService;
use Modules\School\Services\Contracts\SchoolService;
use Modules\Shared\Livewire\Concerns\ManagesRecords;

class InternshipManager extends Component
{
    use ManagesRecords {
        getPdfData as traitGetPdfData;
    }

    public InternshipForm $form;

    /**
     * Initialize the component.
     */
    public function boot(InternshipService $internshipService): void
    {
        $this->service = $internshipService;
        $this->eventPrefix = 'internship';
    }

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $isSetupPhase =
            session(\Modules\Setup\Services\Contracts\SetupService::SESSION_SETUP_AUTHORIZED) ===
                true || is_testing();

        if ($isSetupPhase) {
            return;
        }

        $this->authorize('internship.view');
    }

    /**
     * Open the form modal for adding a new record.
     */
    public function add(): void
    {
        $this->form->reset();

        // Auto-fill school_id if only one school exists
        $school = app(SchoolService::class)->getSchool();
        if ($school) {
            $this->form->school_id = $school->id;
        }

        $this->formModal = true;
    }

    /**
     * {@inheritdoc}
     */
    protected function getExportHeaders(): array
    {
        return [
            'title' => __('internship::ui.title'),
            'description' => __('ui::common.description'),
            'year' => __('internship::ui.year'),
            'semester' => __('internship::ui.semester'),
            'date_start' => __('internship::ui.date_start'),
            'date_finish' => __('internship::ui.date_finish'),
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function mapRecordForExport($record, array $keys): array
    {
        return [
            $record->title,
            $record->description,
            $record->year,
            $record->semester,
            $record->date_start?->format('Y-m-d'),
            $record->date_finish?->format('Y-m-d'),
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function mapImportRow(array $row, array $keys): ?array
    {
        if (count($row) < 4) {
            return null;
        }

        return [
            'title' => $row[0],
            'description' => $row[1] ?? '',
            'year' => (int) $row[2],
            'semester' => $row[3],
            'date_start' => ! empty($row[4]) ? $row[4] : null,
            'date_finish' => ! empty($row[5]) ? $row[5] : null,
            'school_id' => app(SchoolService::class)->getSchool()?->id,
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getPdfView(): ?string
    {
        return 'internship::pdf.internships';
    }

    /**
     * {@inheritdoc}
     */
    protected function getPdfData($records): array
    {
        return array_merge($this->traitGetPdfData($records), [
            'school' => app(SchoolService::class)->getSchool(),
        ]);
    }

    public function render()
    {
        return view('internship::livewire.internship-manager', [
            'records' => $this->records,
        ])->layout('ui::components.layouts.dashboard', [
            'title' => __('internship::ui.index.title') . ' | ' . setting('brand_name', setting('app_name')),
        ]);
    }
}
