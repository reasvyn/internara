<?php

declare(strict_types=1);

namespace Modules\Internship\Livewire;

use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Modules\Internship\Livewire\Forms\InternshipForm;
use Modules\Internship\Services\Contracts\InternshipService;
use Modules\Permission\Enums\Permission;
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
        $this->modelClass = Internship::class;
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

        $isSetupAuthorized = (bool) session('setup_authorized') || is_testing();

        if (! $isSetupAuthorized) {
            $this->viewPermission = Permission::INTERNSHIP_VIEW;
            $this->createPermission = Permission::INTERNSHIP_MANAGE;
            $this->updatePermission = Permission::INTERNSHIP_MANAGE;
            $this->deletePermission = Permission::INTERNSHIP_MANAGE;
        }

        $this->searchable = ['title', 'description', 'academic_year'];
        $this->sortable = [
            'title',
            'academic_year',
            'semester',
            'date_start',
            'date_finish',
            'created_at',
        ];
    }

    /**
     * Define the table structure.
     */
    protected function getTableHeaders(): array
    {
        return [
            ['key' => 'title', 'label' => __('internship::ui.title'), 'sortable' => true],
            ['key' => 'current_status', 'label' => __('internship::ui.status')],
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
            ['key' => 'actions', 'label' => __('ui::common.actions'), 'class' => 'w-1'],
        ];
    }

    /**
     * Transform raw internship record for UI display.
     */
    protected function mapRecord(mixed $record): array
    {
        return array_merge($record->toArray(), [
            'status_label' => $record->getStatusLabel(),
            'status_color' => $record->getStatusColor(),
            'date_start_formatted' => $record->date_start->translatedFormat('d M Y'),
            'date_finish_formatted' => $record->date_finish->translatedFormat('d M Y'),
            'created_at_formatted' => $record->created_at->format('Y-m-d H:i'),
        ]);
    }

    /**
     * Update the status of an internship program.
     */
    public function updateStatus(string $id, string $status): void
    {
        $this->authorize('internship.manage');

        try {
            $this->service->updateStatus($id, $status);
            flash()->success(__('shared::messages.record_saved'));
            $this->refreshRecords();
        } catch (\Throwable $e) {
            flash()->error($e->getMessage());
        }
    }

    /**
     * Bulk update the status of selected records.
     */
    public function bulkUpdateStatus(string $status): void
    {
        $this->authorize('internship.manage');

        if (empty($this->selectedIds)) {
            return;
        }

        try {
            $this->service->bulkUpdateStatus($this->selectedIds, $status);
            flash()->success(__('shared::messages.record_saved'));
            $this->refreshRecords();
        } catch (\Throwable $e) {
            flash()->error($e->getMessage());
        }
    }

    /**
     * Open the form modal for adding a new record.
     */
    public function add(): void
    {
        $this->form->reset();

        // Standard Auto-fills for institutional consistency
        $this->form->academic_year = (string) setting(
            'active_academic_year',
            date('Y').'/'.(date('Y') + 1),
        );

        $school = app(SchoolService::class)->getSchool();
        if ($school) {
            $this->form->school_id = $school->id;
        }

        $this->toggleModal(self::MODAL_FORM, true);
    }

    /**
     * Reset all applied filters and pagination.
     */
    public function resetFilters(): void
    {
        $this->filters = [];
        $this->selectedIds = [];
        $this->resetPage();
    }

    /**
     * Count the number of active filters.
     */
    public function activeFilterCount(): int
    {
        return count(array_filter($this->filters, fn ($v) => $v !== null && $v !== '' && $v !== []));
    }

    /**
     * Get available academic years for filtering.
     */
    #[Computed]
    public function academicYears(): array
    {
        return DB::table('internships')
            ->select('academic_year')
            ->distinct()
            ->orderBy('academic_year', 'desc')
            ->pluck('academic_year')
            ->toArray();
    }

    /**
     * Get the available semester options for the UI.
     */
    public function getSemesterOptions(): array
    {
        return array_map(
            fn (Semester $semester) => [
                'id' => $semester->value,
                'name' => $semester->label(),
            ],
            Semester::cases(),
        );
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
            $record->semester->value,
            $record->date_start->toDateString(),
            $record->date_finish->toDateString(),
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
        return [
            'records' => $records,
            'date' => now()->translatedFormat('d F Y'),
            'school' => app(SchoolService::class)->getSchool(),
        ];
    }

    /**
     * Render the component.
     */
    public function render(): View
    {
        return view('internship::livewire.internship-manager');
    }
}
