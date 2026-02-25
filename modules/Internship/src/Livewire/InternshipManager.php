<?php

declare(strict_types=1);

namespace Modules\Internship\Livewire;

use Modules\Internship\Livewire\Forms\InternshipForm;
use Modules\Internship\Services\Contracts\InternshipService;
use Modules\School\Services\Contracts\SchoolService;
use Modules\UI\Livewire\RecordManager;

/**
 * Class InternshipManager
 *
 * Orchestrates the management of internship programs, handling lifecycle,
 * periods, and institutional constraints.
 */
class InternshipManager extends RecordManager
{
    public InternshipForm $form;

    /**
     * Initialize the component metadata and services.
     */
    public function boot(InternshipService $internshipService): void
    {
        $this->service = $internshipService;
        $this->eventPrefix = 'internship';
        $this->modelClass = \Modules\Internship\Models\Internship::class;
    }

    /**
     * Configure the component's basic properties.
     */
    public function initialize(): void
    {
        $this->title = __('internship::ui.program_title');
        $this->subtitle = __('internship::ui.program_subtitle');
        $this->context = 'internship::ui.index.title';
        $this->addLabel = __('internship::ui.add_program');
        $this->deleteConfirmMessage = __('internship::ui.delete_program_confirm');

        $isSetupAuthorized =
            session(\Modules\Setup\Services\Contracts\SetupService::SESSION_SETUP_AUTHORIZED) ===
                true || is_testing();

        if (! $isSetupAuthorized) {
            $this->viewPermission = 'internship.view';
            $this->createPermission = 'internship.manage';
            $this->updatePermission = 'internship.manage';
            $this->deletePermission = 'internship.manage';
        }
    }

    /**
     * Define the table structure.
     */
    protected function getTableHeaders(): array
    {
        return [
            ['key' => 'title', 'label' => __('internship::ui.title'), 'sortable' => true],
            [
                'key' => 'academic_year',
                'label' => __('internship::ui.academic_year'),
                'sortable' => true,
            ],
            ['key' => 'semester', 'label' => __('internship::ui.semester'), 'sortable' => true],
            [
                'key' => 'date_start_formatted',
                'label' => __('internship::ui.date_start'),
                'sort_by' => 'date_start',
            ],
            [
                'key' => 'date_finish_formatted',
                'label' => __('internship::ui.date_finish'),
                'sort_by' => 'date_finish',
            ],
            ['key' => 'actions', 'label' => '', 'class' => 'w-1'],
        ];
    }

    /**
     * Transform raw internship record for UI display.
     */
    protected function mapRecord(mixed $record): array
    {
        return array_merge($record->toArray(), [
            'date_start_formatted' => $record->date_start->translatedFormat('d M Y'),
            'date_finish_formatted' => $record->date_finish->translatedFormat('d M Y'),
            'created_at_formatted' => $record->created_at->format('Y-m-d H:i'),
        ]);
    }

    /**
     * Open the form modal for adding a new record.
     */
    public function add(): void
    {
        $this->form->reset();

        // Standard Auto-fills for institutional consistency
        $this->form->academic_year = \Modules\Core\Academic\Support\AcademicYear::current();

        $school = app(SchoolService::class)->getSchool();
        if ($school) {
            $this->form->school_id = $school->id;
        }

        $this->toggleModal(self::MODAL_FORM, true);
    }

    /**
     * {@inheritdoc}
     */
    protected function getExportHeaders(): array
    {
        return [
            'title' => __('internship::ui.title'),
            'description' => __('ui::common.description'),
            'academic_year' => __('internship::ui.academic_year'),
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
            $record->academic_year,
            $record->semester,
            $record->date_start,
            $record->date_finish,
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
            'academic_year' => $row[2],
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
        return array_merge([
            'records' => $records,
            'date' => now()->translatedFormat('d F Y'),
            'school' => app(SchoolService::class)->getSchool(),
        ]);
    }

    /**
     * Render the component.
     */
    public function render(): \Illuminate\View\View
    {
        return view('internship::livewire.internship-manager')->layout(
            'ui::components.layouts.dashboard',
            [
                'title' => $this->title.' | '.setting('brand_name', setting('app_name')),
                'context' => $this->context,
            ],
        );
    }
}
