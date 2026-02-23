<?php

declare(strict_types=1);

namespace Modules\Internship\Livewire;

use Modules\Internship\Livewire\Forms\InternshipForm;
use Modules\Internship\Services\Contracts\InternshipService;
use Modules\School\Services\Contracts\SchoolService;
use Modules\UI\Livewire\RecordManager;

class InternshipManager extends RecordManager
{
    public InternshipForm $form;

    /**
     * Define the model class for Policy-based authorization.
     */
    protected string $modelClass = \Modules\Internship\Models\Internship::class;

    /**
     * Define granular permissions for Internship management.
     */
    protected string $viewPermission = 'internship.view';

    protected string $createPermission = 'internship.manage';

    protected string $updatePermission = 'internship.manage';

    protected string $deletePermission = 'internship.manage';

    /**
     * Initialize the component.
     */
    public function boot(InternshipService $internshipService): void
    {
        $this->service = $internshipService;
        $this->eventPrefix = 'internship';
    }

    /**
     * {@inheritdoc}
     */
    public function initialize(): void
    {
        $this->title = __('internship::ui.program_title');
        $this->subtitle = __('internship::ui.program_subtitle');
        $this->context = 'internship::ui.index.title';
        $this->addLabel = __('internship::ui.add_program');
        $this->deleteConfirmMessage = __('internship::ui.delete_program_confirm');
    }

    /**
     * {@inheritdoc}
     */
    protected function getTableHeaders(): array
    {
        return [
            ['key' => 'title', 'label' => __('internship::ui.title'), 'sortable' => true],
            ['key' => 'academic_year', 'label' => __('internship::ui.academic_year'), 'sortable' => true],
            ['key' => 'semester', 'label' => __('internship::ui.semester'), 'sortable' => true],
            ['key' => 'date_start', 'label' => __('internship::ui.date_start'), 'sortable' => true],
            ['key' => 'date_finish', 'label' => __('internship::ui.date_finish'), 'sortable' => true],
            ['key' => 'actions', 'label' => '', 'class' => 'w-1'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function mapRecord(mixed $record): array
    {
        return [
            'id' => $record->id,
            'title' => $record->title,
            'description' => $record->description,
            'academic_year' => $record->academic_year,
            'semester' => $record->semester,
            'date_start' => $record->date_start->translatedFormat('d M Y'),
            'date_finish' => $record->date_finish->translatedFormat('d M Y'),
            'created_at' => $record->created_at->format('Y-m-d H:i'),
        ];
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
            $record->date_start, // Already formatted string from mapRecord
            $record->date_finish, // Already formatted string from mapRecord
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
        return view('internship::livewire.internship-manager', [
            'records' => $this->records,
            'headers' => $this->getTableHeaders(),
        ])->layout('ui::components.layouts.dashboard', [
            'title' => $this->title.' | '.setting('brand_name', setting('app_name')),
            'context' => $this->context,
        ]);
    }
}
