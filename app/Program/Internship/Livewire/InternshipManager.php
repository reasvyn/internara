<?php

declare(strict_types=1);

namespace App\Program\Internship\Livewire;

use App\Academics\AcademicYear\Models\AcademicYear;
use App\Core\Enums\CsvRowResult;
use App\Core\Exceptions\RejectedException;
use App\Core\Livewire\BaseRecordManager;
use App\Core\Support\CsvHandler;
use App\Enrollment\Placement\Models\Placement;
use App\Enrollment\Registration\Models\Registration;
use App\Program\Internship\Actions\BatchUpdateInternshipStatusAction;
use App\Program\Internship\Actions\CheckCloseReadinessAction;
use App\Program\Internship\Actions\CreateInternshipAction;
use App\Program\Internship\Actions\DeleteInternshipAction;
use App\Program\Internship\Actions\UpdateInternshipAction;
use App\Program\Internship\Enums\InternshipStatus;
use App\Program\Internship\Livewire\Forms\InternshipForm;
use App\Program\Internship\Models\Internship;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Computed;
use Livewire\WithFileUploads;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InternshipManager extends BaseRecordManager
{
    use AuthorizesRequests, WithFileUploads;

    public bool $showModal = false;

    public bool $showConfirm = false;

    public string $confirmMessage = '';

    public string $confirmType = '';

    public ?string $confirmTarget = null;

    public $importFile;

    public ?array $readinessResults = null;

    public ?string $readinessInternshipId = null;

    public InternshipForm $form;

    public function boot(): void
    {
        $this->authorize('viewAny', Internship::class);
    }

    public function headers(): array
    {
        return [
            ['key' => 'name', 'label' => __('internship.batch_name'), 'sortable' => true],
            ['key' => 'start_date', 'label' => __('internship.start_date'), 'sortable' => true],
            ['key' => 'end_date', 'label' => __('internship.end_date'), 'sortable' => true],
            ['key' => 'status', 'label' => __('internship.status'), 'sortable' => true],
            ['key' => 'actions', 'label' => '', 'sortable' => false],
        ];
    }

    protected function query(): Builder
    {
        return Internship::query()
            ->with('academicYear')
            ->withCount(['placements', 'registrations']);
    }

    protected function applySearch(Builder $query): Builder
    {
        return $query->where('name', 'like', "%{$this->search}%");
    }

    protected function applyFilters(Builder $query): Builder
    {
        return $query
            ->when($this->filters['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->when(
                $this->filters['academic_year_id'] ?? null,
                fn ($q, $v) => $q->where('academic_year_id', $v),
            )
            ->when(
                $this->filters['date_from'] ?? null,
                fn ($q, $v) => $q->whereDate('start_date', '>=', $v),
            )
            ->when(
                $this->filters['date_to'] ?? null,
                fn ($q, $v) => $q->whereDate('end_date', '<=', $v),
            );
    }

    #[Computed]
    public function statusOptions(): array
    {
        return collect(InternshipStatus::cases())
            ->map(
                fn ($s) => [
                    'id' => $s->value,
                    'name' => __("internship.statuses.{$s->value}"),
                ],
            )
            ->toArray();
    }

    #[Computed]
    public function academicYears(): array
    {
        return AcademicYear::orderByDesc('start_date')
            ->get()
            ->map(
                fn ($y) => [
                    'id' => $y->id,
                    'name' => $y->name,
                ],
            )
            ->prepend(['id' => '', 'name' => __('internship.select_academic_year')])
            ->toArray();
    }

    #[Computed]
    public function stats(): array
    {
        return [
            'total' => Internship::count(),
            'active' => Internship::where('status', InternshipStatus::ACTIVE->value)->count(),
            'total_placements' => Placement::count(),
            'total_registrations' => Registration::count(),
        ];
    }

    // --- Record Actions ---

    public function create(): void
    {
        $this->resetErrorBag();
        $activeYear = AcademicYear::where('is_active', true)->first();
        $this->form->reset();
        $this->form->academic_year_id = $activeYear?->id ?? '';
        $this->showModal = true;
    }

    public function edit(string $id): void
    {
        $internship = Internship::findOrFail($id);

        $this->resetErrorBag();
        $this->form->fill([
            'id' => $internship->id,
            'name' => $internship->name,
            'academic_year_id' => $internship->academic_year_id ?? '',
            'start_date' => $internship->start_date->format('Y-m-d'),
            'end_date' => $internship->end_date->format('Y-m-d'),
            'registration_start_date' => $internship->registration_start_date?->format('Y-m-d') ?? '',
            'registration_end_date' => $internship->registration_end_date?->format('Y-m-d') ?? '',
            'description' => $internship->description ?? '',
            'status' => $internship->status->value,
        ]);
        $this->showModal = true;
    }

    public function save(CreateInternshipAction $create, UpdateInternshipAction $update): void
    {
        $this->form->registration_start_date = $this->form->registration_start_date ?: null;
        $this->form->registration_end_date = $this->form->registration_end_date ?: null;

        $this->form->validate();

        if ($this->form->id) {
            $internship = Internship::findOrFail($this->form->id);

            try {
                $update->execute($internship, $this->form->all());
                flash()->success(__('internship.update_success'));
            } catch (RejectedException $e) {
                flash()->error($e->getMessage());

                return;
            }
        } else {
            try {
                $create->execute($this->form->all());
                flash()->success(__('internship.save_success'));
            } catch (RejectedException $e) {
                flash()->error($e->getMessage());

                return;
            }
        }

        $this->showModal = false;
    }

    // --- Confirm Dialog ---

    public function askDelete(string $id): void
    {
        $internship = Internship::findOrFail($id);

        $this->confirmTarget = $id;
        $this->confirmType = 'delete';
        $this->confirmMessage = __('internship.confirm_delete', ['name' => $internship->name]);
        $this->showConfirm = true;
    }

    public function askDeleteSelected(): void
    {
        if ($this->selectedIds === []) {
            return;
        }

        $this->confirmTarget = null;
        $this->confirmType = 'delete_selected';
        $this->confirmMessage = __('internship.confirm_delete_selected', [
            'count' => count($this->selectedIds),
        ]);
        $this->showConfirm = true;
    }

    public function askCloseFiltered(): void
    {
        $this->confirmTarget = null;
        $this->confirmType = 'close_filtered';
        $this->confirmMessage = __('internship.confirm_close_filtered');
        $this->showConfirm = true;
    }

    public function confirmAction(
        DeleteInternshipAction $deleteAction,
        BatchUpdateInternshipStatusAction $batchAction,
    ): void {
        if (
            $this->confirmTarget === null &&
            $this->confirmType !== 'close_filtered' &&
            $this->confirmType !== 'delete_selected'
        ) {
            return;
        }

        try {
            match ($this->confirmType) {
                'delete' => $this->executeDelete($this->confirmTarget, $deleteAction),
                'delete_selected' => $this->executeDeleteSelected($deleteAction),
                'close_filtered' => $this->executeCloseFiltered($batchAction),
                default => null,
            };
        } catch (RejectedException $e) {
            flash()->error($e->getMessage());
        }

        $this->showConfirm = false;
        $this->confirmTarget = null;
        $this->confirmType = '';
    }

    private function executeDelete(string $id, DeleteInternshipAction $action): void
    {
        $internship = Internship::findOrFail($id);

        if (! $internship->asInternshipState()->canBeDeleted()) {
            flash()->error(__('internship.delete_blocked'));

            return;
        }

        $action->execute($internship);
        flash()->success(__('internship.delete_success'));
    }

    private function executeDeleteSelected(DeleteInternshipAction $action): void
    {
        $this->performBulkAction('Delete', function ($id) use ($action) {
            $internship = Internship::find($id);
            if ($internship && $internship->asInternshipState()->canBeDeleted()) {
                $action->execute($internship);
            }
        });
    }

    private function executeCloseFiltered(BatchUpdateInternshipStatusAction $action): void
    {
        $this->performMassAction('Close All Filtered', function ($query) use ($action) {
            $action->execute($query, InternshipStatus::COMPLETED);
        });
    }

    // --- Import / Export / Template ---

    public function updatedImportFile(): void
    {
        if ($this->importFile) {
            $this->import(app(CsvHandler::class), app(CreateInternshipAction::class));
        }
    }

    public function import(CsvHandler $csv, CreateInternshipAction $create): void
    {
        $this->validate([
            'importFile' => ['required', 'file', 'mimes:csv,txt', 'max:2048'],
        ]);

        $activeYear = AcademicYear::where('is_active', true)->first();

        $result = $csv->import($this->importFile->getRealPath(), function (array $row) use (
            $activeYear,
            $create,
        ) {
            $name = trim($row[0] ?? '');

            if ($name === '') {
                return null;
            }

            if (Internship::where('name', $name)->exists()) {
                return CsvRowResult::SKIPPED;
            }

            $create->execute([
                'name' => $name,
                'description' => trim($row[1] ?? '') ?: null,
                'academic_year_id' => $activeYear?->id,
                'start_date' => $activeYear?->start_date ?? now(),
                'end_date' => $activeYear?->end_date ?? now()->addYear(),
                'status' => InternshipStatus::DRAFT->value,
            ]);

            return CsvRowResult::CREATED;
        });

        $this->importFile = null;

        if ($result['invalid']) {
            flash()->error(__('internship.import_invalid'));

            return;
        }

        flash()->success(
            __('internship.import_summary', [
                'created' => $result['created'],
                'skipped' => $result['skipped'],
            ]),
        );
    }

    public function export(CsvHandler $csv): StreamedResponse
    {
        $internships = Internship::with('academicYear')
            ->when($this->search, fn ($q) => $q->where('name', 'like', "%{$this->search}%"))
            ->orderBy('name')
            ->get();

        return $csv->export(
            $internships,
            ['name', 'description', 'status', 'start_date', 'end_date'],
            fn ($i) => [
                $i->name,
                $i->description ?? '',
                $i->status->value,
                $i->start_date->format('Y-m-d'),
                $i->end_date->format('Y-m-d'),
            ],
            'internships.csv',
        );
    }

    public function exportSelected(CsvHandler $csv): ?StreamedResponse
    {
        if ($this->selectedIds === []) {
            flash()->warning(__('common.actions.no_records_selected'));

            return null;
        }

        $internships = Internship::with('academicYear')
            ->whereIn('id', $this->selectedIds)
            ->orderBy('name')
            ->get();

        return $csv->export(
            $internships,
            ['name', 'description', 'status', 'start_date', 'end_date'],
            fn ($i) => [
                $i->name,
                $i->description ?? '',
                $i->status->value,
                $i->start_date->format('Y-m-d'),
                $i->end_date->format('Y-m-d'),
            ],
            'internships-selected.csv',
        );
    }

    public function downloadTemplate(CsvHandler $csv): StreamedResponse
    {
        return $csv->downloadTemplate(
            ['name', 'description', 'status', 'start_date', 'end_date'],
            [
                __('internship.template_example_name'),
                __('internship.template_example_description'),
                'draft',
                now()->format('Y-m-d'),
                now()->addYear()->format('Y-m-d'),
            ],
            'internships-template.csv',
        );
    }

    // --- Pre-Close Readiness ---

    public function checkReadiness(string $internshipId, CheckCloseReadinessAction $action): void
    {
        $internship = Internship::findOrFail($internshipId);
        $this->readinessResults = $action->execute($internship);
        $this->readinessInternshipId = $internshipId;
    }

    public function dismissReadiness(): void
    {
        $this->readinessResults = null;
        $this->readinessInternshipId = null;
    }

    public function render(): View
    {
        return view('program.internship.internship-manager');
    }
}
